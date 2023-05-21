<?php

namespace App\Kata;

use App\Kata\Enums\KataRunnerIterationMode;
use App\Kata\Enums\KataRunnerMode;
use App\Kata\Exceptions\KataChallengeScoreException;
use App\Kata\Objects\KataChallengeResultObject;
use App\Kata\Traits\HasExitHintsTrait;
use App\Kata\Utilities\CodeUtility;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;
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

    protected const DEFAULT_ITERATION_MODES = [
        KataRunnerIterationMode::MAX_ITERATIONS,
        KataRunnerIterationMode::MAX_SECONDS,
    ];

    protected array $modes;

    protected array $iterationModes;

    protected Carbon $createdAt;

    protected array $kataChallenges;

    protected ?ProgressBar $progressBar = null;

    protected array $report = [];

    public function __construct(
        protected ?Command $command = null,
        protected array $challenges = []
    ) {
        $this->createdAt = now();

        $configChallenges = config('laravel-kata.challenges');

        if (! empty($challenges)) {
            foreach ($challenges as $challengeClass) {
                if (! in_array($challengeClass, $configChallenges)) {
                    throw new Exception(sprintf(
                        'Challenge not found in config "laravel-kata.challenges", expected: %s, available %s',
                        $challengeClass,
                        implode(', ', $configChallenges)
                    ));
                }

                if (! class_exists($challengeClass)) {
                    throw new Exception(sprintf(
                        'Challenge not found: %s',
                        $challengeClass
                    ));
                }
            }
        }

        $this->kataChallenges = ! empty($challenges) ? $challenges : config('laravel-kata.challenges');

        $this->modes = self::DEFAULT_MODES;

        $this->iterationModes = self::DEFAULT_ITERATION_MODES;

        if (! is_null($this->command) && ! config('laravel-kata.progress-bar-disabled')) {
            $this->progressBar = $this->command?->getOutput()->createProgressBar(0);
            $this->progressBar->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%%");
        }
    }

    public function run(?string $method = null): array
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
                    fn ($challenge) => str_replace('App\\Kata\\Challenges\\A\\', '', $challenge)
                )->join(', '),
            ],
        ]);

        foreach ($this->kataChallenges as $kataChallenge) {
            $this->handleChallenge($kataChallenge, $method);
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

        $getScoreRow = function (string $field, ?string $title = null) use ($reportData): array {
            $valueA = data_get($reportData, sprintf('stats.a.%s', $field));
            $valueB = data_get($reportData, sprintf('stats.b.%s', $field));

            $displayValueA = match ($field) {
                'execution_time_avg' => time_to_human($valueA),
                'memory_usage_avg' => bytes_to_human($valueA),
                default => $valueA,
            };

            $displayValueB = match ($field) {
                'execution_time_avg' => time_to_human($valueB),
                'memory_usage_avg' => bytes_to_human($valueB),
                default => $valueB,
            };

            $success = data_get($reportData, sprintf('stats.b.%s_gains_success', $field));
            $gainsPerc = data_get($reportData, sprintf('stats.b.%s_gains_perc', $field));

            $performance = match ($field) {
                'outputs_md5' => wrap_in_format($success ? '100%' : '0%', $success),
                default => wrap_in_format(sprintf('%s%%', $gainsPerc), $success, warn: true),
            };

            return [
                $title ?: $field,
                $displayValueA,
                $displayValueB,
                $performance,
            ];
        };

        $gainsPerc = data_get($reportData, 'stats.b.gains_perc');

        $title = sprintf(
            '%s::%s (%s%%)',
            $resultA->getClassName(),
            $resultA->getMethodName(),
            $gainsPerc
        );

        $this->report('newLine');
        $this->report(
            'line',
            wrap_in_format($title, $gainsPerc >= config('laravel-kata.gains-perc-minimum'), warn: true)
        );

        // Show where it comes from
        $this->report('line', sprintf(
            'A: %s', help_me_code($resultA->getReflectionMethod()),
        ));
        if (config('laravel-kata.show-code-snippets')) {
            $this->report('comment', $resultA->getCodeSnippet());
        }
        $this->report('line', sprintf(
            'B: %s', help_me_code($resultB->getReflectionMethod()),
        ));
        if (config('laravel-kata.show-code-snippets')) {
            $this->report('comment', $resultB->getCodeSnippet());
        }

        $this->report('table',
            [
                '',
                'A               ',
                'B               ',
                'Gains           ',
            ],
            [
                $getScoreRow('line_count', 'Lines'),
                $getScoreRow('iteration_count', 'Iterations'),
                $getScoreRow('execution_time_avg', 'Execution time'),
                $getScoreRow('memory_usage_avg', 'Memory usage'),
            ]
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
        if (app()->runningInConsole()) {
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
            'memory_usage_avg' => 'lt',
            'execution_time_avg' => 'lt',
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
                        ? round(abs(($value1 - $value2) / $value1) * 100, 2)
                        : 0;
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
            'line_count_gains_perc' => 0.1,
            'memory_usage_avg_gains_perc' => 0.2,
            'iteration_count_gains_perc' => 0.35,
            'execution_time_avg_gains_perc' => 0.35,
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

        if (config('laravel-kata.debug-mode')) {
            $this->addExitHintsFromViolations($statsA['violations']);
        }

        $this->addExitHintsFromViolations($statsB['violations']);

        return $result;
    }

    protected function handleChallenge(
        string $kataChallenge,
        ?string $method = null
    ): void {
        $kataChallengeReflection = new ReflectionClass($kataChallenge);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            // We only run public methods
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            // We don't want to handle the base class
            if ($reflectionMethod->class === KataChallenge::class) {
                continue;
            }

            if (is_null($method) || $reflectionMethod->name !== $method) {
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

            // Change reflection method based on the mode
            $reflectionClass = new ReflectionClass($targetClass);
            $reflectionMethod = $reflectionClass->getMethod($reflectionMethod->name);
        }

        if (! class_exists($targetClass)) {
            throw new Exception(sprintf('Class not found %s', $targetClass));
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

            $challengeOutputs['memory_usage_peak'] = memory_get_peak_usage(false);
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

        $response = null;
        switch ($kataRunnerIterationMode) {
            case KataRunnerIterationMode::MAX_ITERATIONS:
                $response = $this->runChallengeMethodMaxIterations(
                    $reflectionMethod,
                    $maxIterations
                );
                break;
            case KataRunnerIterationMode::MAX_SECONDS:
                $response = $this->runChallengeMethodMaxSeconds(
                    $reflectionMethod,
                    $maxSeconds
                );
                break;
            default:
                throw new Exception(sprintf(
                    'Unexpected kata run iteration mode of "%s"',
                    $kataRunnerIterationMode->value
                ));
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
        $memoryUsageSum = 0;
        $executionTimeSum = 0;
        $startTime = microtime(true);
        $outputs = [];

        $this->progressBar?->setMaxSteps($iterationCount);
        $this->progressBar?->setProgress(0);
        for ($iteration = 0; $iteration < $iterationCount; $iteration++) {
            $className = $reflectionMethod->class;
            $instance = app()->make($className);
            $methodName = $reflectionMethod->name;
            $this->progressBar?->setMessage(sprintf(
                '%s->%s(%d) [interations]',
                $className,
                $methodName,
                $iteration + 1
            ));

            $executionTimeSum += Benchmark::measure(function () use ($instance, $methodName, $iteration, &$outputs) {
                $outputs[] = $instance->{$methodName}($iteration + 1);
            });

            $memoryUsageSum += $instance->getMemoryUsage();
            $instance = null;
            $this->progressBar?->advance();
        }

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
            'memory_usage_sum' => $memoryUsageSum,
            'memory_usage_avg' => $memoryUsageSum / $iterationCount,
            'execution_time' => microtime(true) - $startTime,
            'execution_time_sum' => $executionTimeSum,
            'execution_time_avg' => $executionTimeSum / $iterationCount,
        ];
    }

    protected function runChallengeMethodMaxSeconds(
        ReflectionMethod $reflectionMethod,
        int $maxSeconds
    ): array {
        $executionTimeSum = 0;
        $memoryUsageSum = 0;
        $startTime = microtime(true);
        $msMax = $maxSeconds * 1000;
        $dateTimeEnd = now()->addMilliseconds($msMax);
        $outputs = [];

        $this->progressBar?->setMaxSteps($msMax);
        $this->progressBar?->setProgress(0);

        $iterationCount = 0;
        do {
            $msLeft = now()->diffInMilliseconds($dateTimeEnd, false);

            $iterationCount++;
            $className = $reflectionMethod->class;
            $instance = app()->make($className);
            $methodName = $reflectionMethod->name;

            $executionTimeSum += Benchmark::measure(function () use ($instance, $methodName, $iterationCount, &$outputs) {
                $outputs[] = $instance->{$methodName}($iterationCount);
            });

            $memoryUsageSum += $instance->getMemoryUsage();
            $instance = null;
            $this->progressBar?->setProgress($msMax - $msLeft);
            $this->progressBar?->setMessage(sprintf(
                '%s->%s(%d) [duration]',
                $className,
                $methodName,
                $iterationCount
            ));
        } while ($msLeft > 0);

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
            'outputs' => $outputs,
            'iteration_count' => $iterationCount,
            'memory_usage_sum' => $memoryUsageSum,
            'memory_usage_avg' => $memoryUsageSum / $iterationCount,
            'execution_time' => microtime(true) - $startTime,
            'execution_time_sum' => $executionTimeSum,
            'execution_time_avg' => $executionTimeSum / $iterationCount,
        ];
    }
}
