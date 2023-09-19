<?php

namespace App;

use App\Enums\KataRunnerIterationMode;
use App\Enums\KataRunnerMode;
use App\Exceptions\KataChallengeNotFoundException;
use App\Exceptions\KataChallengeScoreException;
use App\Objects\KataChallengeResultObject;
use App\Traits\HasExitHintsTrait;
use App\Utilities\Benchmark;
use App\Utilities\CodeUtility;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Helper\ProgressBar;

class KataRunner
{
    use HasExitHintsTrait;

    protected const DEFAULT_MODES = [
        KataRunnerMode::A,
        KataRunnerMode::B,
    ];

    protected array $modes;

    protected array $iterationModes;

    protected Carbon $createdAt;

    protected array $kataChallenges;

    protected ?ProgressBar $progressBar = null;

    protected array $report = [];

    public function __construct(
        protected ?Command $command = null,
        protected array $challenges = [],
        protected array $methods = []
    ) {
        $this->createdAt = now();

        $configChallenges = config('laravel-kata.challenges');

        if (! empty($challenges)) {
            foreach ($challenges as $challengeClass) {
                if (! class_exists($challengeClass)) {
                    throw new KataChallengeNotFoundException(sprintf(
                        'Challenge not found: %s',
                        $challengeClass
                    ));
                }

                if (! in_array($challengeClass, $configChallenges)) {
                    throw new KataChallengeNotFoundException(sprintf(
                        'Challenge not found in config "laravel-kata.challenges", expected: %s, available %s',
                        $challengeClass,
                        implode(', ', $configChallenges)
                    ));
                }
            }
        }

        $this->kataChallenges = ! empty($challenges) ? $challenges : config('laravel-kata.challenges');

        $this->modes = self::DEFAULT_MODES;

        $this->iterationModes = config('laravel-kata.modes');
        if (app()->runningUnitTests()) {
            $this->iterationModes = collect(config('laravel-kata.modes'))
                ->reject(fn (string $mode) => KataRunnerIterationMode::XDEBUG_PROFILE->value)
                ->values();
        }

        if (! is_null($this->command) && ! config('laravel-kata.progress-bar-disabled')) {
            $this->progressBar = $this->command?->getOutput()->createProgressBar(0);
            $this->progressBar->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%%");
        }
    }

    public function run(): array
    {
        $this->command?->info('Laravel Kata Run');
        $this->command?->table([
            'Variable',
            'Value',
        ], [
            [
                'Seconds',
                config('laravel-kata.max-seconds'),
            ],
            [
                'Iterations',
                config('laravel-kata.max-iterations'),
            ],
            [
                'Challenges',
                collect($this->kataChallenges)->map(
                    fn ($challenge) => str_replace('App\\Challenges\\A\\', '', $challenge)
                )->join(', '),
            ],
        ]);

        foreach ($this->kataChallenges as $kataChallenge) {
            $this->handleChallenge($kataChallenge);
        }

        return [
            'report' => $this->report,
        ];
    }

    protected function printReport(
        KataChallengeResultObject $resultA,
        KataChallengeResultObject $resultB
    ): void {
        $reportData = $this->getReportData($resultA, $resultB);

        $getScoreRow = function (string $field, string $title = null, float $weight = 0.0) use ($reportData): array {
            $valueA = data_get($reportData, sprintf('stats.a.%s', $field));
            $valueB = data_get($reportData, sprintf('stats.b.%s', $field));

            $displayValueA = match ($field) {
                'execution_time_avg' => time_to_human($valueA),
                'execution_time_sum' => time_to_human($valueA),
                'profile_time_avg' => time_to_human($valueA),
                'profile_memory_usage_avg' => bytes_to_human($valueA),
                default => $valueA,
            };

            $displayValueB = match ($field) {
                'execution_time_avg' => time_to_human($valueB),
                'execution_time_sum' => time_to_human($valueB),
                'profile_time_avg' => time_to_human($valueB),
                'profile_memory_usage_avg' => bytes_to_human($valueB),
                default => $valueB,
            };

            $success = data_get($reportData, sprintf('stats.b.%s_gains_success', $field));
            $gainsPerc = data_get($reportData, sprintf('stats.b.%s_gains_perc', $field));

            $performance = match ($field) {
                'outputs_md5' => wrap_in_format($success ? '100%' : '0%', $success),
                default => wrap_in_format(sprintf('%s%%', round($gainsPerc)), $success, warn: true),
            };

            return [
                $title ?: $field,
                $displayValueA,
                $displayValueB,
                $performance,
                sprintf('%s: %s', number_format($weight, 2), number_format($weight * $gainsPerc, 2)),
            ];
        };

        $gainsPerc = data_get($reportData, 'stats.b.gains_perc');
        $title = sprintf(
            '%s::%s (%sX)',
            $resultA->getClassName(),
            $resultA->getMethodName(),
            round($gainsPerc / 100, 2)
        );
        $this->report('newLine');
        $this->report(
            'line',
            wrap_in_format($title, $gainsPerc >= config('laravel-kata.gains-perc-minimum'), warn: true)
        );

        // Show where it comes from
        if (config('laravel-kata.show-code-snippets')) {
            $this->report('line', sprintf(
                'A: %s', help_me_code($resultA->getReflectionMethod()),
            ));
            $this->report('comment', $resultA->getCodeSnippet());

            $this->report('line', sprintf(
                'B: %s', help_me_code($resultB->getReflectionMethod()),
            ));
            $this->report('comment', $resultB->getCodeSnippet());
        }

        $this->report('table',
            [
                '',
                'A               ',
                'B               ',
                'Gains           ',
                'Weighted gains  ',
            ],
            [
                $getScoreRow('violations_count', 'Code / Violations', 0.05),
                $getScoreRow('line_count', 'Code / Lines', 0.05),
                $getScoreRow('iteration_count', sprintf(
                    'Benchmark / Iterations in %ss',
                    config('laravel-kata.max-seconds'),
                ), 0.20),
                $getScoreRow('execution_time_sum', sprintf(
                    'Benchmark / Execution time for %dx',
                    data_get($reportData, 'stats.a._result.max-iterations.iteration_count', 52),
                ), 0.20),
                $getScoreRow('profile_time_avg', 'Profiling / Execution time (avg)', 0.25),
                $getScoreRow('profile_memory_usage_avg', 'Profiling / Memory usage (avg)', 0.25),
                [
                    '',
                    '',
                    '',
                    '',
                    sprintf('1.00: %s', number_format($gainsPerc, 2)),
                ],
            ],
        );

        if (! data_get($reportData, 'stats.b.outputs_md5_gains_success')) {
            $this->report('newLine');
            $this->report('warn', 'The outputs did not match!');
            $this->report('newLine');
            $this->report('info', 'Outputs');
            $this->report('info', 'A->first()');
            $this->report('line', sprintf("```\n%s\n```", $resultA->getOutputsJsonFirst()));
            $this->report('info', 'B->first()');
            $this->report('line', sprintf("```\n%s\n```", $resultB->getOutputsJsonFirst()));

            $this->report('info', 'A->last()');
            $this->report('line', sprintf("```\n%s\n```", $resultA->getOutputsJsonLast()));
            $this->report('info', 'B->last()');
            $this->report('line', sprintf("```\n%s\n```", $resultB->getOutputsJsonLast()));
        }

        // Outputs should always match
        if (! data_get($reportData, 'stats.b.outputs_md5_gains_success')) {
            throw new KataChallengeScoreException(sprintf(
                'Outputs does not match (expected: %s, actual: %s)',
                data_get($reportData, 'stats.a.outputs_md5'),
                data_get($reportData, 'stats.b.outputs_md5'),
            ));
        }

        // Fail when lower than expected score
        if ($gainsPerc < config('laravel-kata.gains-perc-minimum')) {
            throw new KataChallengeScoreException(sprintf(
                'Score is lower than expected (%s%% < %s%%)',
                round($gainsPerc, 2),
                round(config('laravel-kata.gains-perc-minimum'), 2),
            ));
        }
    }

    protected function report(...$args): void
    {
        if (app()->runningInConsole() && ! is_null($this->command)) {
            $this->reportConsole(...$args);

            return;
        }

        if ($args[0] === 'newLine') {
            return;
        }

        if ($args[0] === 'table') {
            $this->report[] = [
                'type' => 'table',
                'headers' => $args[1],
                'rows' => $args[2],
            ];

            return;
        }

        $this->report[] = [
            'type' => $args[0],
            'value' => $args[1],
        ];
    }

    protected function reportConsole(...$args): void
    {
        match (count($args)) {
            1 => $this->command->{$args[0]}(),
            2 => $this->command->{$args[0]}($args[1]),
            3 => $this->command->{$args[0]}($args[1], $args[2])
        };
    }

    /**
     * Calculate and append gains
     *
     * Future ideas
     * - Benchmark with K6
     * - Resource usage Grafana
     */
    protected function calculateGains(array $statsA, array $statsB): array
    {
        $fields = [
            'outputs_md5' => 'string',
            'line_count' => 'lt',
            'violations_count' => 'lt',
            'iteration_count' => 'gt',
            'profile_memory_usage_avg' => 'lt',
            'execution_time_avg' => 'lt',
            'execution_time_sum' => 'lt',
            'profile_time_avg' => 'lt',
        ];

        foreach ($fields as $field => $mode) {
            $success = false;
            $value1 = $statsB[$field];
            $value2 = $statsA[$field];

            if (in_array($mode, ['lt', 'gt'])) {
                if ($mode === 'gt') {
                    $value1 = $value2;
                    $value2 = $statsB[$field];
                }

                $gains = $value1 - $value2;

                if ($value2 > 0 && $value1 == 0) {
                    $percDiff = 100;
                } else {
                    $percDiff = $value1 != 0
                        ? abs(($value1 - $value2) / $value1) * 100
                        : 0;
                }

                if ($value1 > $value2) {
                    $percDiff *= -1;
                }

                $success = $value1 <= $value2;
            }

            if ($mode === 'string') {
                $gains = 0;
                $percDiff = 0;
                $success = $value1 === $value2;
            }

            $statsB[sprintf('%s_gains_diff', $field)] = $gains;
            $statsB[sprintf('%s_gains_perc', $field)] = $percDiff;
            $statsB[sprintf('%s_gains_success', $field)] = $success;
        }

        $gainsWeights = [
            'violations_count_gains_perc' => 0.05,
            'line_count_gains_perc' => 0.05,
            'iteration_count_gains_perc' => 0.20,
            'execution_time_sum_gains_perc' => 0.20,
            'profile_time_avg_gains_perc' => 0.25,
            'profile_memory_usage_avg_gains_perc' => 0.25,
        ];

        $statsB['gains_perc'] = collect($gainsWeights)->map(
            fn ($weight, $key) => $statsB[$key] * $weight
        )->sum();

        ksort($statsB);

        return $statsB;
    }

    protected function getReportData(
        KataChallengeResultObject $resultA,
        KataChallengeResultObject $resultB,
    ): array {
        $statsA = $resultA->getStats();
        $statsB = $this->calculateGains(
            $statsA,
            $resultB->getStats()
        );

        $className = $resultA->getClassName();
        $methodName = $resultA->getMethodName();

        // Save as json output
        $result = [
            'class' => $className,
            'method' => $methodName,
            'stats' => [
                'a' => $statsA,
                'b' => $statsB,
            ],
        ];

        if (config('laravel-kata.save-results-to-storage')) {
            $filePath = sprintf(
                'laravel-kata/%s/result-%s.json',
                $this->createdAt->format('Ymd-His'),
                Str::slug(implode(' ', [$className, $methodName])),
            );

            Storage::disk('local')->put($filePath, json_encode($result));
        }

        $this->addExitHintsFromViolations($statsA['violations']);
        $this->addExitHintsFromViolations($statsB['violations']);

        return $result;
    }

    protected function handleChallenge(string $kataChallenge): void
    {
        $kataChallengeReflection = new ReflectionClass($kataChallenge);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            // Ignore any methods starting with "before"
            if (Str::startsWith($reflectionMethod->name, 'before')) {
                continue;
            }

            // We don't want to handle the base class
            if ($reflectionMethod->class === KataChallenge::class) {
                continue;
            }

            // Filter by methods
            if (! empty($this->methods) && ! in_array($reflectionMethod->name, $this->methods)) {
                continue;
            }

            $result = $this->handleChallengeMethod($reflectionMethod);

            if (! is_null($result)) {
                $this->printReport(
                    $result[KataRunnerMode::A->value],
                    $result[KataRunnerMode::B->value]
                );
            }
        }
    }

    /**
     * Run the challenge method
     *
     * Started with a simple approach where the object is instantiated once, but
     * changed to instantiate for each method instead.
     *
     * This will give us some hooks, similar to unit tests like setUp(), and tearDown()
     */
    protected function handleChallengeMethod(ReflectionMethod $reflectionMethod): array
    {
        $results = [];
        foreach ($this->modes as $mode) {
            $results[$mode->value] = $this->runChallengeMethod($reflectionMethod, $mode);
        }

        return $results;
    }

    protected function runChallengeMethod(
        ReflectionMethod $reflectionMethod,
        KataRunnerMode $mode = KataRunnerMode::A
    ): KataChallengeResultObject {
        $targetClass = $reflectionMethod->class;
        if ($mode === KataRunnerMode::B) {
            $targetClass = str_replace('\\A\\', '\\B\\', $reflectionMethod->class);

            if (! class_exists($targetClass)) {
                throw new KataChallengeNotFoundException(sprintf(
                    'Expected class %s not found',
                    $targetClass
                ));
            }

            // Change reflection method based on the mode
            $reflectionClass = new ReflectionClass($targetClass);
            $reflectionMethod = $reflectionClass->getMethod($reflectionMethod->name);
        }

        /** @var KataChallenge $instance */
        $instance = new $targetClass();
        $maxIterations = $instance->getMaxIterations();
        $maxSeconds = $instance->getMaxSeconds();
        $instance = null;

        $result = [];
        foreach ($this->iterationModes as $iterationMode) {
            $challengeOutputs = $this->runChallengeMethodMaxMode(
                $reflectionMethod,
                $iterationMode,
                $maxIterations,
                $maxSeconds,
            );

            $result[$iterationMode->value] = $challengeOutputs;
        }

        return new KataChallengeResultObject($reflectionMethod, $result);
    }

    protected function runChallengeMethodMaxMode(
        ReflectionMethod $reflectionMethod,
        KataRunnerIterationMode $kataRunnerIterationMode,
        int $maxIterations,
        int $maxSeconds,
    ): array {
        $cacheKey = sprintf(
            '%s:%s-%ds-%dx-%s',
            $kataRunnerIterationMode->value,
            $reflectionMethod->name,
            $maxSeconds,
            $maxIterations,
            md5(sprintf(
                '%s-%s-%s',
                $reflectionMethod->class,
                $reflectionMethod->name,
                CodeUtility::getCodeMd5($reflectionMethod)
            ))
        );

        if (config('laravel-kata.experimental.cache-results') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = match ($kataRunnerIterationMode) {
            KataRunnerIterationMode::MAX_ITERATIONS => $this->runChallengeMethodMaxIterations(
                $reflectionMethod,
                $maxIterations
            ),
            KataRunnerIterationMode::MAX_SECONDS => $this->runChallengeMethodMaxSeconds(
                $reflectionMethod,
                $maxSeconds
            ),
            KataRunnerIterationMode::XDEBUG_PROFILE => $this->runChallengeMethodProfile(
                $reflectionMethod,
            )
        };

        if (config('laravel-kata.experimental.cache-results')) {
            Cache::set($cacheKey, $response);
        }

        return $response;
    }

    protected function runChallengeMethodMaxIterations(
        ReflectionMethod $reflectionMethod,
        int $iterationCount
    ): array {
        $className = $reflectionMethod->class;
        $instance = app()->make($className);
        $methodName = $reflectionMethod->name;

        $beforeMethod = sprintf('before%s', ucfirst($methodName));
        if (method_exists($instance, $beforeMethod)) {
            $instance->{$beforeMethod}();
        }

        $executionTimeSum = 0;
        $startTime = microtime(true);
        $outputs = [];

        $this->progressBar?->setMaxSteps($iterationCount);
        $this->progressBar?->setProgress(0);
        for ($iteration = 0; $iteration < $iterationCount; $iteration++) {
            $this->progressBar?->setMessage(sprintf(
                '%s->%s(%d) [interations]',
                $className,
                $methodName,
                $iteration + 1
            ));

            $executionTimeSum += Benchmark::measure(function () use ($instance, $methodName, $iteration, &$outputs) {
                $outputs[] = $instance->{$methodName}($iteration + 1);
            });

            $this->progressBar?->advance();
        }

        $instance = null;
        $this->progressBar?->finish();
        $this->progressBar?->clear();

        $outputsMd5 = md5(json_encode($outputs));
        if ($iterationCount > 2) {
            $outputs = [
                $outputs[0],
                $outputs[$iterationCount - 1],
            ];
        }

        return [
            'outputs_json' => json_encode($outputs),
            'outputs_md5' => $outputsMd5,
            'iteration_count' => $iterationCount,
            'execution_time' => microtime(true) - $startTime,
            'execution_time_sum' => $executionTimeSum,
            'execution_time_avg' => $executionTimeSum / $iterationCount,
        ];
    }

    protected function runChallengeMethodMaxSeconds(
        ReflectionMethod $reflectionMethod,
        int $maxSeconds
    ): array {
        $className = $reflectionMethod->class;
        $instance = app()->make($className);
        $methodName = $reflectionMethod->name;

        $beforeMethod = sprintf('before%s', ucfirst($methodName));
        if (method_exists($instance, $beforeMethod)) {
            $instance->{$beforeMethod}();
        }

        $executionTimeSum = 0;
        $startTime = microtime(true);
        $msMax = $maxSeconds * 1000;
        $dateTimeEnd = now()->addMilliseconds($msMax);
        $outputs = [];

        $this->progressBar?->setMaxSteps($msMax);
        $this->progressBar?->setProgress(0);
        $iteration = 0;
        do {
            $iteration++;
            $msLeft = now()->diffInMilliseconds($dateTimeEnd, false);

            $executionTimeSum += Benchmark::measure(function () use ($instance, $methodName, $iteration, &$outputs) {
                $outputs[] = $instance->{$methodName}($iteration);
            });

            $this->progressBar?->setProgress($msMax - $msLeft);
            $this->progressBar?->setMessage(sprintf(
                '%s->%s(%d) [duration]',
                $className,
                $methodName,
                $iteration
            ));
        } while ($msLeft > 0);

        $instance = null;
        $this->progressBar?->finish();
        $this->progressBar?->clear();

        $outputsMd5 = md5(json_encode($outputs));
        if ($iteration > 2) {
            $outputs = [
                $outputs[0],
                $outputs[$iteration - 1],
            ];
        }

        return [
            'outputs_json' => json_encode($outputs),
            'outputs_md5' => $outputsMd5,
            'outputs' => $outputs,
            'iteration_count' => $iteration,
            'execution_time' => microtime(true) - $startTime,
            'execution_time_sum' => $executionTimeSum,
            'execution_time_avg' => $executionTimeSum / $iteration,
        ];
    }

    protected function runChallengeMethodProfile(ReflectionMethod $reflectionMethod): array
    {
        $className = $reflectionMethod->class;
        /** @var \App\KataChallenge $instance */
        $instance = app()->make($className);
        $methodName = $reflectionMethod->name;

        $maxIterations = ceil($instance->getMaxIterations() / 2);

        return Benchmark::profile(fn () => $instance->{$methodName}($maxIterations), maxIterations: $maxIterations);
    }
}
