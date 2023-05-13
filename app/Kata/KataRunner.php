<?php

namespace App\Kata;

use App\Kata\Enums\KataRunnerIterationMode;
use App\Kata\Enums\KataRunnerMode;
use App\Kata\Exceptions\KataChallengeScoreException;
use App\Kata\Objects\KataChallengeResultObject;
use App\Kata\Traits\HasExitHintsTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class KataRunner
{
    use HasExitHintsTrait;

    protected const CHALLENGE_SUFFIX = 'Record';

    protected const DEFAULT_MODES = [
        KataRunnerMode::BEFORE,
        KataRunnerMode::RECORD,
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

    public function __construct(
        protected ?Command $command = null,
        protected bool $failOnScore = false,
        protected array $challenges = []
    ) {
        $this->createdAt = now();
        $this->command = $command;

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

        defined('KATA_BASE_MEM_USED') or define('KATA_BASE_MEM_USED', memory_get_usage(true));
    }

    public function run(): Collection
    {
        $results = collect();

        foreach ($this->kataChallenges as $kataChallenge) {
            $result = $this->handleChallenge($kataChallenge);
            $results->push($result);
        }

        return $results;
    }

    protected function reportResult(array $result): void
    {
        /** @var KataChallengeResultObject $resultBefore */
        $resultBefore = $result[KataRunnerMode::BEFORE->value];

        /** @var KataChallengeResultObject $resultRecord */
        $resultRecord = $result[KataRunnerMode::RECORD->value];

        $this->printScoresTable($resultBefore, $resultRecord);
    }

    protected function printScoresTable(
        KataChallengeResultObject $resultBefore,
        KataChallengeResultObject $resultRecord
    ): void {
        $reportData = $this->getReportData($resultBefore, $resultRecord);

        $getScoreRow = fn (string $field, ?string $title = null): array => [
            $title ?: $field,
            data_get($reportData, sprintf('stats.before.%s', $field)),
            sprintf(
                '%s %s',
                data_get($reportData, sprintf('stats.record.%s', $field)),
                wrap_in_format(
                    sprintf('(%s%%)', data_get($reportData, sprintf('stats.record.%s_gains_perc', $field))),
                    data_get($reportData, sprintf('stats.record.%s_gains_success', $field)),
                ),
            ),
        ];

        $this->command->table(
            [
                '',
                sprintf('A) %s::%s', $resultBefore->getClassName(), $resultBefore->getMethodName()),
                sprintf('B) %s::%s', $resultRecord->getClassName(), $resultRecord->getMethodName()),
            ],
            [
                [
                    'File',
                    help_me_code($resultBefore->getReflectionMethod()),
                    help_me_code($resultRecord->getReflectionMethod()),
                ],
                config('laravel-kata.show-code-snippets') ? [
                    'Code',
                    $resultBefore->getCodeSnippet(),
                    $resultRecord->getCodeSnippet(),
                ] : [],
                $getScoreRow('outputs_md5', 'Outputs'),
                $getScoreRow('line_count', 'Lines'),
                $getScoreRow('violations_count', 'Violations'),
                $getScoreRow('iterations', 'Iterations'),
                $getScoreRow('duration', 'Duration'),
            ]
        );
        $this->command->line(sprintf(
            '* Iterations: The amount of times this function executed in %d seconds',
            config('laravel-kata.max-seconds')
        ));
        $this->command->line(sprintf(
            '* Duration: The execution time (ms) it took to run the function %d times',
            config('laravel-kata.max-iterations')
        ));

        if (config('laravel-kata.outputs-show-last')) {
            $this->command->newLine();
            $this->command->info('Outputs of last iteration');
            $this->command->info('Output from A');
            $this->command->line(sprintf("```\n%s\n```", $resultBefore->getOutputsJsonLast()));
            $this->command->info('Output from B');
            $this->command->line(sprintf("```\n%s\n```", $resultRecord->getOutputsJsonLast()));
        }

        // Minimum percentage
        $minSuccessPerc = config('laravel-kata.min-success-perc');
        $successPerc = data_get($reportData, 'stats.record.success_perc');
        if ($successPerc < $minSuccessPerc) {
            throw new KataChallengeScoreException(sprintf(
                'Success percentage is below the minimum, %s%% < %s%%',
                round($successPerc * 100, 2),
                round($minSuccessPerc * 100, 2),
            ));
        }

        // Outputs should always match
        if (! data_get($reportData, 'stats.record.outputs_md5_gains_success')) {
            throw new KataChallengeScoreException(sprintf(
                'Outputs does not match (expected: %s, actual: %s)',
                data_get($reportData, 'stats.before.outputs_md5'),
                data_get($reportData, 'stats.record.outputs_md5'),
            ));
        }
    }

    /**
     * Calculate and append gains
     *
     * Future:
     * - Baseline stats in math
     * - Benchmark with K6
     * - Resource usage Grafana
     */
    protected function calculateGains(
        array $statsBefore,
        array $statsRecord
    ): array {
        $fields = [
            'outputs_md5' => 'string',
            'line_count' => 'lt',
            'violations_count' => 'lt',
            'duration' => 'lt',
            'iterations' => 'gt',
        ];

        foreach ($fields as $field => $mode) {
            $success = false;
            $value1 = $statsRecord[$field];
            $value2 = $statsBefore[$field];

            if (in_array($mode, ['lt', 'gt'])) {
                if ($mode === 'gt') {
                    $value1 = $value2;
                    $value2 = $statsRecord[$field];
                }

                $gains = $value1 - $value2;

                $percDiff = $value1 !== 0
                    ? round(abs(($value1 - $value2) / $value1) * 100, 2)
                    : 0;

                $success = $value1 <= $value2;
            }

            if ($mode === 'string') {
                $gains = 0;
                $percDiff = 0;
                $success = $value1 === $value2;
            }

            $statsRecord[sprintf('%s_gains_diff', $field)] = $gains;
            $statsRecord[sprintf('%s_gains_perc', $field)] = $percDiff;
            $statsRecord[sprintf('%s_gains_success', $field)] = $success;
        }

        $statsRecord['gains_perc'] = collect($statsRecord)->map(
            fn ($value, $key) => str_ends_with($key, 'gains_perc') ? $value : null
        )->filter()->average();

        $statsRecord['gains_success'] = collect($statsRecord)->filter(
            fn ($value, $key) => str_ends_with($key, 'gains_success') &&
                ! str_starts_with($key, 'line_count') &&
                ! str_starts_with($key, 'violations_count') &&
                $value === false
        )->count() === 0;

        $successFields = collect($statsRecord)->filter(
            fn ($_, $key) => str_ends_with($key, '_success')
        );
        $statsRecord['success_max'] = $successFields->count();
        $statsRecord['success_count'] = $successFields->filter()->count();
        $statsRecord['success_perc'] = $statsRecord['success_count'] / $statsRecord['success_max'];

        ksort($statsRecord);

        return $statsRecord;
    }

    protected function getReportData(
        KataChallengeResultObject $resultBefore,
        KataChallengeResultObject $resultRecord,
    ): array {
        // Get the baseline stats once only
        $baselineMethod = $resultBefore->getBaselineReflectionMethod();
        $cacheKey = sprintf('%s.%s', Str::slug($baselineMethod->class), $baselineMethod->name);
        if (! isset($this->resultBaselineCache[$cacheKey])) {
            $resultBaseline = $this->runChallengeMethod($resultBefore->getBaselineReflectionMethod());
            $this->resultBaselineCache[$cacheKey] = $resultBaseline->getStats();
        }
        $statsBaseline = $this->resultBaselineCache[$cacheKey];
        $statsBefore = $resultBefore->getStats();
        $statsRecord = $this->calculateGains(
            $statsBefore,
            $resultRecord->getStats()
        );

        $className = $resultBefore->getClassName();
        $methodName = $resultBefore->getMethodName();

        // Save as json output
        $result = [
            'class' => $className,
            'method' => $methodName,
            'stats' => [
                'baseline' => $statsBaseline,
                'before' => $statsBefore,
                'record' => $statsRecord,
            ],
        ];

        if (config('laravel-kata.outputs-save')) {
            $filePath = sprintf(
                'laravel-kata/%s/result-%s.json',
                $this->createdAt->format('Ymd-His'),
                Str::slug(implode(' ', [$className, $methodName])),
            );

            Storage::disk('local')->put($filePath, json_encode($result));
            $this->command?->line(sprintf('Report saved to: %s', $filePath));
        }

        if (config('laravel-kata.debug-mode')) {
            $this->addExitHintsFromViolations($statsBaseline['violations']);
            $this->addExitHintsFromViolations($statsBefore['violations']);
        }

        $this->addExitHintsFromViolations($statsRecord['violations']);

        return $result;
    }

    protected function handleChallenge(string $kataChallenge): array
    {
        $results = [];
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
                $this->reportResult($result);
                $results[$reflectionMethod->name] = $result;
            }
        }

        return $results;
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
        KataRunnerMode $mode = KataRunnerMode::BEFORE
    ): KataChallengeResultObject {
        $targetClass = $reflectionMethod->class;
        if ($mode === KataRunnerMode::RECORD) {
            $classParts = explode('\\', $reflectionMethod->class);
            $className = sprintf('%s%s', array_pop($classParts), self::CHALLENGE_SUFFIX);
            array_push($classParts, $className);
            $targetClass = implode('\\', $classParts);

            // Change reflection method based on the mode
            $reflectionClass = new ReflectionClass($targetClass);
            $reflectionMethod = $reflectionClass->getMethod($reflectionMethod->name);
        }

        if (! class_exists($targetClass)) {
            throw new Exception(sprintf('Class not found %s', $targetClass));
        }

        // Instantiate for the following reasons
        // - Warm up the reference/op caching
        // - Get the max iterations & seconds
        $instance = new $targetClass();
        $maxIterations = $instance->getMaxIterations();
        $maxSeconds = $instance->getMaxSeconds();
        $instance = null;

        $result = [];
        foreach ($this->iterationModes as $iterationMode) {
            $memoryUsedBase = memory_get_usage(true);
            $challengeOutputs = $this->runChallengeMethodMaxMode(
                $reflectionMethod,
                $iterationMode,
                $maxIterations,
                $maxSeconds,
            );

            $memoryUsed = $memoryUsedBase - memory_get_usage(true);
            $memoryUsedPeak = memory_get_peak_usage(true);

            // Exception: If zero, should fail!
            if (empty($challengeOutputs['outputs'])) {
                throw new Exception(sprintf(
                    'Unexpected empty outputs from %s->%s()',
                    $reflectionMethod->class,
                    $reflectionMethod->name,
                ));
            }

            $result[$iterationMode->value] = [
                'outputs' => $challengeOutputs['outputs'],
                'event' => $challengeOutputs['event'],
                'memoryUsed' => $memoryUsed,
                'memoryUsedPeak' => $memoryUsedPeak,
            ];
        }

        // Loop again to separate the concerns
        foreach ($this->iterationModes as $iterationMode) {
            $result[$iterationMode->value]['outputs_count'] = count($result[$iterationMode->value]['outputs']);

            $result[$iterationMode->value]['outputs_json'] = json_encode($result[$iterationMode->value]['outputs']);
            $result[$iterationMode->value]['outputs_md5'] = md5($result[$iterationMode->value]['outputs_json']);
            $result[$iterationMode->value]['duration'] = $result[$iterationMode->value]['event']->duration();

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
        $event = clock()->event(
            sprintf('%s->%s() (%s)', $reflectionMethod->class, $reflectionMethod->name, $kataRunnerIterationMode->value)
        )->color('green')->begin();

        $outputs = null;
        switch ($kataRunnerIterationMode) {
            case KataRunnerIterationMode::MAX_ITERATIONS:
                $outputs = $this->runChallengeMethodMaxIterations(
                    $reflectionMethod,
                    $maxIterations
                );
                break;
            case KataRunnerIterationMode::MAX_SECONDS:
                $outputs = $this->runChallengeMethodMaxSeconds(
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

        $event->end();

        return [
            'event' => $event,
            'outputs' => $outputs,
        ];
    }

    protected function runChallengeMethodMaxIterations(
        ReflectionMethod $reflectionMethod,
        int $maxIterations
    ): array {
        $outputs = [];

        $bar = $this->command?->getOutput()->createProgressBar($maxIterations);
        $bar?->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%%");
        foreach (range(1, $maxIterations) as $iteration) {
            $className = $reflectionMethod->class;
            $instance = app($className);
            $methodName = $reflectionMethod->name;
            $bar?->setMessage(sprintf(
                '%s->%s(%d) [interations]',
                $className,
                $methodName,
                $iteration
            ));

            $outputs[] = $instance->{$methodName}($iteration);

            $bar?->advance();
            $instance = null;
        }

        $bar?->finish();
        $this->command?->newLine();

        return $outputs;
    }

    protected function runChallengeMethodMaxSeconds(
        ReflectionMethod $reflectionMethod,
        int $maxSeconds
    ): array {
        $msMax = $maxSeconds * 1000;
        $dateTimeEnd = now()->addMilliseconds($msMax);
        $outputs = [];

        /** @var ProgressBar $bar */
        $bar = $this->command?->getOutput()->createProgressBar($msMax);
        $bar?->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%%");

        $iteration = 0;
        do {
            $msLeft = now()->diffInMilliseconds($dateTimeEnd, false);

            $iteration++;
            $className = $reflectionMethod->class;
            $instance = app($className);
            $methodName = $reflectionMethod->name;
            $outputs[] = $instance->{$methodName}($iteration);
            $instance = null;

            $bar?->setProgress($msMax - $msLeft);
            $bar?->setMessage(sprintf(
                '%s->%s(%d) [duration]',
                $className,
                $methodName,
                $iteration
            ));
        } while ($msLeft > 0);

        $bar?->finish();
        $this->command?->newLine();

        return $outputs;
    }
}
