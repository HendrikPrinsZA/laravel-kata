<?php

namespace App;

use App\Enums\KataRunnerIterationMode;
use App\Enums\KataRunnerMode;
use App\Exceptions\KataChallengeNotFoundException;
use App\Exceptions\KataChallengeScoreException;
use App\Exceptions\KataChallengeScoreOutputsMd5Exception;
use App\Objects\KataChallengeResultObject;
use App\Traits\HasExitHintsTrait;
use App\Utilities\Benchmark;
use App\Utilities\CodeUtility;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDOException;
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
                ->reject(fn (KataRunnerIterationMode $mode) => $mode === KataRunnerIterationMode::XDEBUG_PROFILE)
                ->values()
                ->toArray();
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
                'Mode (LK_RUN_MODE)',
                config('laravel-kata.mode')->name,
            ],
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

        $getScoreRow = function (string $field, ?string $title = null, float $weight = 0.0) use ($reportData): array {
            $valueA = data_get($reportData, sprintf('stats.a.%s', $field));
            $valueB = data_get($reportData, sprintf('stats.b.%s', $field));

            if (is_null($valueA) || is_null($valueB)) {
                return [
                    $title ?: $field,
                    $valueA,
                    $valueB,
                    'N/A',
                    'N/A',
                ];
            }

            $displayValueA = match ($field) {
                'execution_time_avg' => time_to_human($valueA),
                'execution_time_sum' => time_to_human($valueA),
                'profile_time_avg' => time_to_human($valueA),
                'profile_memory_usage_avg' => sprintf(
                    '%s (%s)',
                    bytes_to_human($valueA),
                    $valueA
                ),
                default => $valueA,
            };

            $displayValueB = match ($field) {
                'execution_time_avg' => time_to_human($valueB),
                'execution_time_sum' => time_to_human($valueB),
                'profile_time_avg' => time_to_human($valueB),
                'profile_memory_usage_avg' => sprintf(
                    '%s (%s)',
                    bytes_to_human($valueB),
                    $valueB
                ),
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

        $this->validateOutputs($reportData, KataRunnerIterationMode::MAX_ITERATIONS);
        $this->validateOutputs($reportData, KataRunnerIterationMode::MAX_SECONDS);

        // Fail when lower than expected score
        if ($gainsPerc < config('laravel-kata.gains-perc-minimum')) {
            throw new KataChallengeScoreException(sprintf(
                'Score is lower than expected (%s%% < %s%%)',
                round($gainsPerc, 2),
                round(config('laravel-kata.gains-perc-minimum'), 2),
            ));
        }
    }

    /**
     * Validate the outputs from the report data
     *
     * Rules:
     * - Outputs for MAX_ITERATIONS and MAX_SECONDS should match
     * - Compare equal sets for MAX_SECONDS, i.e. a has 10, b has 11 -> only compare first 10
     */
    protected function validateOutputs(array $reportData, KataRunnerIterationMode $kataRunnerIterationMode): void
    {
        $outputsA = data_get($reportData, sprintf('stats.a._result.%s.outputs', $kataRunnerIterationMode->value), []);
        $outputsB = data_get($reportData, sprintf('stats.b._result.%s.outputs', $kataRunnerIterationMode->value), []);

        $countA = count($outputsA);
        $countB = count($outputsB);

        // Balance when MAX_SECOND
        if ($kataRunnerIterationMode === KataRunnerIterationMode::MAX_SECONDS) {
            $minCount = min($countA, $countB);

            $outputsA = array_slice($outputsA, 0, $minCount);
            $outputsB = array_slice($outputsB, 0, $minCount);
        }

        if (md5(json_encode($outputsA)) !== md5(json_encode($outputsB))) {
            $this->report('newLine');
            $this->report('warn', sprintf('The outputs did not match for %s!', $kataRunnerIterationMode->value));
            $this->report('newLine');
            $this->report('info', 'Outputs');
            $this->report('info', 'A->first()');
            $this->report('line', sprintf("```\n%s\n```", $outputsA[0]));
            $this->report('info', 'B->first()');
            $this->report('line', sprintf("```\n%s\n```", $outputsB[0]));

            $this->report('info', sprintf('A->last() -> at ', $countA));
            $this->report('line', sprintf("```\n%s\n```", $outputsA[$countA - 1]));
            $this->report('info', sprintf('B->last() -> at ', $countB));
            $this->report('line', sprintf("```\n%s\n```", $outputsB[$countB - 1]));

            throw new KataChallengeScoreOutputsMd5Exception(sprintf(
                'The outputs did not match for %s (A: %s, B: %s)',
                $kataRunnerIterationMode->value,
                json_encode($outputsA),
                json_encode($outputsB),
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
            $value1 = data_get($statsB, $field);
            $value2 = data_get($statsA, $field);

            if (is_null($value1) || is_null($value2)) {
                $gains = 'N/A';
                $percDiff = 0;
                $success = false;
            } elseif (in_array($mode, ['lt', 'gt'])) {
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
            } elseif ($mode === 'string') {
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
        $instance = new $targetClass;
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

        try {
            $response = match ($kataRunnerIterationMode) {
                KataRunnerIterationMode::MAX_ITERATIONS => $this->runChallengeMethodMaxIterations(
                    $reflectionMethod,
                    $maxIterations
                ),
                KataRunnerIterationMode::MAX_SECONDS => $this->runChallengeMethodMaxSeconds(
                    $reflectionMethod,
                    $maxSeconds
                ),
                KataRunnerIterationMode::XDEBUG_PROFILE => $this->profile(
                    $reflectionMethod,
                )
            };
        } catch (PDOException $exception) {
            throw new PDOException(
                sprintf('[%s] %s: %s', $exception->getCode(), $exception::class, Str::limit($exception->getMessage(), 512)),
                previous: $exception
            );
        }

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
        $maxIterationsMaxSeconds = config('laravel-kata.max-iterations-max-seconds');
        $startTime = microtime(true);
        $outputs = [];

        $this->progressBar?->setMaxSteps($iterationCount);
        $this->progressBar?->setProgress(0);
        for ($iteration = 0; $iteration < $iterationCount; $iteration++) {
            $this->progressBar?->setMessage(sprintf(
                '%s->%s(%d) [interations] (max time: %s/%s)',
                $className,
                $methodName,
                $iteration + 1,
                number_format(($executionTimeSum / 1000), 2),
                number_format($maxIterationsMaxSeconds, 2),
            ));

            $executionTimeSum += Benchmark::measure(function () use ($instance, $methodName, $iteration, &$outputs) {
                $outputs[] = $instance->{$methodName}($iteration + 1);
            });

            $this->progressBar?->advance();
            if (($executionTimeSum / 1000) > $maxIterationsMaxSeconds) {
                break;
            }
        }

        $instance = null;
        $this->progressBar?->finish();
        $this->progressBar?->clear();

        return [
            'outputs' => $outputs,
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

        return [
            'outputs' => $outputs,
            'iteration_count' => $iteration,
            'execution_time' => microtime(true) - $startTime,
            'execution_time_sum' => $executionTimeSum,
            'execution_time_avg' => $executionTimeSum / $iteration,
        ];
    }

    protected function profile(ReflectionMethod $reflectionMethod): array
    {
        $className = $reflectionMethod->class;
        /** @var \App\KataChallenge $instance */
        $instance = app()->make($className);
        $methodName = $reflectionMethod->name;

        $maxIterations = ceil($instance->getMaxIterations() / 2);

        return Benchmark::profile(fn () => $instance->{$methodName}($maxIterations), maxIterations: $maxIterations);
    }
}
