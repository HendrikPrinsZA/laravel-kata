<?php

namespace App\Kata;

use App\Kata\Enums\KataRunnerIterationMode;
use App\Kata\Enums\KataRunnerMode;
use App\Kata\Exceptions\KataChallengeScoreException;
use App\Kata\Objects\KataChallengeResultObject;
use App\Kata\Traits\HasExitHintsTrait;
use App\Kata\Utilities\CodeUtility;
use App\Kata\Utilities\PerformanceUtility;
use Carbon\Carbon;
use Exception;
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

    protected const DEFAULT_ITERATION_MODES = [
        KataRunnerIterationMode::MAX_ITERATIONS,
        KataRunnerIterationMode::MAX_SECONDS,
    ];

    protected array $modes;

    protected array $iterationModes;

    protected array $resultBaselineCache = [];

    protected Carbon $createdAt;

    protected array $kataChallenges;

    protected ?ProgressBar $progressBar = null;

    protected PerformanceUtility $performance;

    protected array $report = [];

    public function __construct(
        protected ?Command $command = null,
        protected array $challenges = []
    ) {
        $this->createdAt = now();
        $this->performance = PerformanceUtility::make();

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

    public function run(): array
    {
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
                $getScoreRow('iterations', 'Iterations'),
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
     * Future:
     * - Baseline stats in math
     * - Benchmark with K6
     * - Resource usage Grafana
     */
    protected function calculateGains(array $statsA, array $statsB): array
    {
        $fields = [
            'outputs_md5' => 'string',
            'line_count' => 'lt',
            'violations_count' => 'lt',
            'iterations' => 'gt',
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
            'iterations_gains_perc' => 0.35,
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
        $baselineMethod = $resultA->getBaselineReflectionMethod();
        $cacheKey = sprintf('%s.%s', Str::slug($baselineMethod->class), $baselineMethod->name);
        if (! isset($this->resultBaselineCache[$cacheKey])) {
            $resultBaseline = $this->runChallengeMethod($resultA->getBaselineReflectionMethod());
            $this->resultBaselineCache[$cacheKey] = $resultBaseline->getStats();
        }
        $statsBaseline = $this->resultBaselineCache[$cacheKey];
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
                'baseline' => $statsBaseline,
                'a' => $statsA,
                'b' => $statsB,
            ],
        ];

        if (config('laravel-kata.outputs-save')) {
            $filePath = sprintf(
                'laravel-kata/%s/result-%s.json',
                $this->createdAt->format('Ymd-His'),
                Str::slug(implode(' ', [$className, $methodName])),
            );

            Storage::disk('local')->put($filePath, json_encode($result));
        }

        if (config('laravel-kata.debug-mode')) {
            $this->addExitHintsFromViolations($statsBaseline['violations']);
            $this->addExitHintsFromViolations($statsA['violations']);
        }

        $this->addExitHintsFromViolations($statsB['violations']);

        return $result;
    }

    protected function handleChallenge(string $kataChallenge): void
    {
        $kataChallengeReflection = new ReflectionClass($kataChallenge);

        $skipFunctions = [
            '__construct',
            'baseline',
        ];

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            // We only run public methods
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            // Skip the baseline function
            if (in_array($reflectionMethod->name, $skipFunctions)) {
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
    protected function handleChallengeMethod(ReflectionMethod $reflectionMethod): ?array
    {
        // We don't want to handle the base class
        if ($reflectionMethod->class === KataChallenge::class) {
            return null;
        }

        $outputs = [];
        foreach ($this->modes as $mode) {
            $outputs[$mode->value] = $this->runChallengeMethod($reflectionMethod, $mode);
        }

        return $outputs;
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

            // Exception: If zero, should fail!
            if (empty($challengeOutputs['outputs'])) {
                throw new Exception(sprintf(
                    'Unexpected empty outputs from %s->%s()',
                    $reflectionMethod->class,
                    $reflectionMethod->name,
                ));
            }

            $challengeOutputs['memory_usage_peak'] = memory_get_peak_usage(false);
            $result[$iterationMode->value] = $challengeOutputs;
        }

        // Loop again to separate the concerns
        foreach ($this->iterationModes as $iterationMode) {
            $result[$iterationMode->value]['outputs_count'] = count($result[$iterationMode->value]['outputs']);

            $result[$iterationMode->value]['outputs_json'] = json_encode($result[$iterationMode->value]['outputs']);
            $result[$iterationMode->value]['outputs_md5'] = md5($result[$iterationMode->value]['outputs_json']);

            // Unset expensive keys
            unset($result[$iterationMode->value]['outputs']);
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
        int $maxIterations
    ): array {
        $memoryUsageSum = 0;
        $startTime = microtime(true);
        $this->performance->reset();
        $outputs = [];

        $this->progressBar?->setMaxSteps($maxIterations);
        $this->progressBar?->setProgress(0);
        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $className = $reflectionMethod->class;
            $instance = app()->make($className);
            $methodName = $reflectionMethod->name;
            $this->progressBar?->setMessage(sprintf(
                '%s->%s(%d) [interations]',
                $className,
                $methodName,
                $iteration + 1
            ));

            $outputs[] = $this->performance->run(
                fn () => $instance->{$methodName}($iteration)
            );

            $memoryUsageSum += $instance->getMemoryUsage();
            $instance = null;
            $this->progressBar?->advance();
        }

        $this->progressBar?->finish();
        $this->progressBar?->clear();

        return [
            'outputs' => $outputs,
            'performance_count' => $this->performance->getCount(),
            'memory_usage_sum' => $memoryUsageSum,
            'memory_usage_avg' => $memoryUsageSum / $this->performance->getCount(),
            'execution_time' => microtime(true) - $startTime,
            'execution_time_sum' => $this->performance->getExecutionTimeSum(),
            'execution_time_avg' => $this->performance->getExecutionTimeAvg(),
        ];
    }

    protected function runChallengeMethodMaxSeconds(
        ReflectionMethod $reflectionMethod,
        int $maxSeconds
    ): array {
        $memoryUsageSum = 0;
        $startTime = microtime(true);
        $this->performance->reset();
        $msMax = $maxSeconds * 1000;
        $dateTimeEnd = now()->addMilliseconds($msMax);
        $outputs = [];

        $this->progressBar?->setMaxSteps($msMax);
        $this->progressBar?->setProgress(0);

        $iteration = 0;
        do {
            $msLeft = now()->diffInMilliseconds($dateTimeEnd, false);

            $iteration++;
            $className = $reflectionMethod->class;
            $instance = app()->make($className);
            $methodName = $reflectionMethod->name;

            $outputs[] = $this->performance->run(
                fn () => $instance->{$methodName}($iteration)
            );

            $memoryUsageSum += $instance->getMemoryUsage();
            $instance = null;
            $this->progressBar?->setProgress($msMax - $msLeft);
            $this->progressBar?->setMessage(sprintf(
                '%s->%s(%d) [duration]',
                $className,
                $methodName,
                $iteration
            ));
        } while ($msLeft > 0);

        $this->progressBar?->finish();
        $this->progressBar?->clear();

        return [
            'outputs' => $outputs,
            'performance_count' => $this->performance->getCount(),
            'memory_usage_sum' => $memoryUsageSum,
            'memory_usage_avg' => $memoryUsageSum / $this->performance->getCount(),
            'execution_time' => microtime(true) - $startTime,
            'execution_time_sum' => $this->performance->getExecutionTimeSum(),
            'execution_time_avg' => $this->performance->getExecutionTimeAvg(),
        ];
    }
}
