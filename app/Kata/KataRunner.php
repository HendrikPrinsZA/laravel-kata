<?php

namespace App\Kata;

use App\Kata\Challenges\KataChallengeSample;
use App\Kata\Enums\KataRunnerIterationMode;
use App\Kata\Enums\KataRunnerMode;
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

    public function __construct(protected ?Command $command = null)
    {
        $this->createdAt = now();
        $this->command = $command;
        $this->kataChallenges = config('laravel-kata.challenges', [
            KataChallengeSample::class,
        ]);

        $mode = $this->command?->option('mode') ?? null;

        if ($mode === 'all') {
            $mode = null;
        }

        $this->modes = $mode === null
            ? self::DEFAULT_MODES
            : [KataRunnerMode::from($mode)];

        $this->iterationModes = self::DEFAULT_ITERATION_MODES;

        defined('KATA_BASE_MEM_USED') or define('KATA_BASE_MEM_USED', memory_get_usage(true));
    }

    public function run(): Collection
    {
        $results = collect();

        foreach ($this->kataChallenges as $kataChallenge) {
            $results->push($this->handleChallenge($kataChallenge));
        }

        $this->report($results);

        return $results;
    }

    protected function report(Collection $results): void
    {
        $showExtendedScores = config('laravel-kata.show-extended-scores');

        $headers = $showExtendedScores ? [
            'Field',
            'Report',
            'Stats (Before)',
            'Stats (Record)',
            'Score (Before)',
            'Score (Record)',
            'Field',
        ] : [
            'Field',
            'Report',
        ];

        foreach ($results as $methodResults) {
            foreach ($methodResults as $method => $methodResult) {
                // TODO: Move to filter before
                // - Something bad with dynamic class methods
                if (! $methodResult) {
                    continue;
                }

                /** @var KataChallengeResultObject $resultBefore */
                $resultBefore = $methodResult[KataRunnerMode::BEFORE->value];

                /** @var KataChallengeResultObject $resultRecord */
                $resultRecord = $methodResult[KataRunnerMode::RECORD->value];

                $reportData = $this->getReportData($resultBefore, $resultRecord);

                $reportText = implode("\n", [
                    sprintf(
                        '%s (%s)',
                        $reportData['stats']['record']['line_count'],
                        $reportData['stats']['before']['line_count']
                    ),
                    sprintf(
                        '%s (%s)',
                        count($reportData['stats']['record']['violations']),
                        count($reportData['stats']['before']['violations'])
                    ),
                    sprintf(
                        '%s (%s)',
                        round($reportData['stats']['record']['duration'], 2),
                        round($reportData['stats']['before']['duration'], 2)
                    ),
                    sprintf(
                        '%s (%s)',
                        $reportData['stats']['record']['iterations'],
                        $reportData['stats']['before']['iterations']
                    ),
                    sprintf(
                        '%s (%s)',
                        $this->wrapInFormat(round($reportData['stats']['record']['scores']['total'], 2),
                            $reportData['stats']['record']['scores']['total'] < $reportData['stats']['before']['scores']['total']
                        ),
                        round($reportData['stats']['before']['scores']['total'], 2)
                    ),
                ]);

                $keys = [
                    'line_count',
                    'violations',
                    'duration',
                    'iterations',
                ];

                $linesBefore = [];
                $linesRecord = [];
                foreach ($keys as $key) {
                    $linesBefore[] = $reportData['stats']['before']['scores'][$key];
                    $linesRecord[] = $reportData['stats']['record']['scores'][$key];
                }
                $linesBefore[] = $reportData['stats']['before']['scores']['total'];
                $linesRecord[] = $reportData['stats']['record']['scores']['total'];
                $scoresBeforeText = implode("\n", $linesBefore);
                $scoresRecordText = implode("\n", $linesRecord);

                $keys[] = 'score';

                $keysLookup = implode("\n", array_values($keys));

                $row = $showExtendedScores ? [
                    $keysLookup,
                    $reportText,
                    $resultBefore->getStatsAsText(),
                    $resultRecord->getStatsAsText(),
                    $scoresBeforeText,
                    $scoresRecordText,
                    $keysLookup,
                ] : [
                    $keysLookup,
                    $reportText,
                ];

                if (config('laravel-kata.show-code-snippets')) {
                    $this->command->info(sprintf('# %s', help_me_code($resultRecord->getReflectionMethod())));
                    $this->command->table([
                        '',
                        'Before',
                        'Record',
                    ], [
                        [
                            'Code',
                            $resultBefore->getCodeSnippet(),
                            $resultRecord->getCodeSnippet(),
                        ],
                        [
                            'Outputs md5',
                            $resultBefore->getOutputsMd5(),
                            $resultRecord->getOutputsMd5(),
                        ],
                        [
                            implode("\n", [
                                'line_count',
                                'violations',
                                'duration',
                                'iterations',
                            ]),
                            $resultBefore->getStatsAsText(),
                            $resultRecord->getStatsAsText(),
                        ],
                    ]);
                }

                $this->command->info('## Report');
                $this->command->table($headers, [$row]);
            }
        }
    }

    /**
     * Calculate the score
     *
     * Breakdown
     * - 5%: code lines
     * - 5%: code violations
     * - 45%: total seconds based on max iterations
     * - 45%: total iterations based on max seconds
     *
     * Future:
     * - Score based on resources (10%)
     *   - 70%: Memory
     *   - 30%: CPU
     * - Include benchmark scores (10%)
     *   - 50%: Max concurrency before max response time threshold
     *   - 50%: Average response time based on X threads (config.max_threads)
     */
    protected function calculateScores(
        array $statsBaseline,
        array &$statsBefore,
        array &$statsRecord
    ): float {
        $statsBefore['scores'] = [
            'line_count' => percentage_change(
                $statsBaseline['line_count'],
                $statsBefore['line_count']
            ),
            'violations' => percentage_change(
                5,
                count($statsBefore['violations']),
                true
            ),
            'duration' => percentage_change(
                $statsBaseline['duration'],
                $statsBefore['duration']
            ),
            'iterations' => percentage_change(
                $statsBaseline['iterations'],
                $statsBefore['iterations'],
                true
            ),
        ];

        $statsRecord['scores'] = [
            'line_count' => percentage_change(
                $statsBaseline['line_count'],
                $statsRecord['line_count']
            ),
            'violations' => percentage_change(
                5,
                count($statsRecord['violations']),
                true
            ),
            'duration' => percentage_change(
                $statsBaseline['duration'],
                $statsRecord['duration']
            ),
            'iterations' => percentage_change(
                $statsBaseline['iterations'],
                $statsRecord['iterations'],
                true
            ),
        ];

        $statsBefore['scores']['total'] = array_sum([
            $statsBefore['scores']['line_count'] * 0.05,
            $statsBefore['scores']['violations'] * 0.05,
            $statsBefore['scores']['duration'] * 0.45,
            $statsBefore['scores']['iterations'] * 0.45,
        ]);

        $statsRecord['scores']['total'] = array_sum([
            $statsRecord['scores']['line_count'] * 0.05,
            $statsRecord['scores']['violations'] * 0.05,
            $statsRecord['scores']['duration'] * 0.45,
            $statsRecord['scores']['iterations'] * 0.45,
        ]);

        return $statsRecord['scores']['total'];
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

        // Get stats
        $statsBefore = $resultBefore->getStats();
        $statsRecord = $resultRecord->getStats();

        $score = $this->calculateScores(
            $statsBaseline,
            $statsBefore,
            $statsRecord
        );

        $className = $resultBefore->getClassName();
        $methodName = $resultBefore->getMethodName();

        // Save as json output
        $result = [
            'class' => $className,
            'method' => $methodName,
            'score' => $score,
            'stats' => [
                'baseline' => $statsBaseline,
                'before' => $statsBefore,
                'record' => $statsRecord,
            ],
        ];

        if (config('laravel-kata.save-outputs')) {
            $filePath = sprintf(
                'laravel-kata/%s/result-%s.json',
                $this->createdAt->format('Ymd-His'),
                Str::slug(implode(' ', [$className, $methodName])),
            );

            Storage::disk('local')->put($filePath, json_encode($result));
            $this->command?->info(sprintf('Saved output to %s', $filePath));
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
        $result = [];
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

            $result[$reflectionMethod->name] = $this->handleChallengeMethod($reflectionMethod);
        }

        return $result;
    }

    /**
     * Run the challenge method
     *
     * Started with a simple approach where the object is instantiated once, but
     * changed to instantiate for each method instead.
     *
     * This will give us some hooks, similar to unit tests like setUp(), and tearDown()
     *
     * Returns true when
     *  1. Results match
     *  2. Is faster
     */
    protected function handleChallengeMethod(ReflectionMethod $reflectionMethod): array|bool
    {
        // What, why?
        if ($reflectionMethod->class === KataChallenge::class) {
            return false;
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
            $result[$iterationMode->value]['outputs_md5'] = md5(json_encode($result[$iterationMode->value]['outputs']));
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
            'event' => &$event,
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

    protected function wrapInFormat(string $string, bool $success): string
    {
        return $success
            ? sprintf('<fg=green>%s</>', $string)
            : sprintf('<fg=red>%s</>', $string);

        $el = $success
            ? 'info'
            : 'warn';

        return sprintf('<%s>%s</%s>', $el, $string, $el);
    }
}
