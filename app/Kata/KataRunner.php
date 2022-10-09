<?php

namespace App\Kata;

use App\Kata\Challenges\KataChallengeEloquent;
use App\Kata\Challenges\KataChallengePhp;
use App\Kata\Challenges\KataChallengeSample;
use App\Kata\Enums\KataRunnerIterationMode;
use App\Kata\Enums\KataRunnerMode;
use App\Kata\Objects\KataChallengeResultObject;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Helper\ProgressBar;

class KataRunner
{
    protected const CHALLENGE_SUFFIX = 'Attempt';

    protected const DEFAULT_MODES = [
        KataRunnerMode::BEFORE,
        KataRunnerMode::ATTEMPT,
    ];

    protected const DEFAULT_ITERATION_MODES = [
        KataRunnerIterationMode::MAX_ITERATIONS,
        KataRunnerIterationMode::MAX_SECONDS,
    ];

    protected array $modes;

    protected array $iterationModes;

    protected int $defaultPericision;

    protected array $kataChallenges = [
        KataChallengeSample::class,
        // KataChallengeEloquent::class,
        // KataChallengePhp::class,
    ];

    public function __construct(protected ?Command $command)
    {
        $mode = $this->command?->option('mode') ?? null;

        if ($mode === 'all') {
            $mode = null;
        }

        $this->modes = $mode === null
            ? self::DEFAULT_MODES
            : [KataRunnerMode::from($mode)];

        $this->iterationModes = self::DEFAULT_ITERATION_MODES;

        $this->defaultPericision = config('laravel-kata.precision');

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
        foreach ($results as $methodResults) {
            foreach ($methodResults as $method => $methodResult) {
                // TODO: Move to filter before
                // - Something bad with dynamic class methods
                if (!$methodResult) { continue; }

                /** @var KataChallengeResultObject $resultBefore */
                $resultBefore = $methodResult[KataRunnerMode::BEFORE->value];

                /** @var KataChallengeResultObject $resultAttempt */
                $resultAttempt = $methodResult[KataRunnerMode::ATTEMPT->value];

                $rows[] = $this->toReportRow($resultBefore, $resultAttempt);
                continue;

                // Blank row to split
                $rows[] = [''];
            }
        }

        $this->command->table([
            'Class',
            'Method',
            'Run',
            'Duration',
            'Iterations',
            'Duration (rel)',
            'Iterations (rel)',
            'Output md5'
        ], $rows);
    }

    // score = (10% lines) + (10% memory) + (40% duration %) + (40% iterations)

    /**
     * Calculate the score, the lower the better
     *
     * Breakdown
     * - 10%: lines of code
     * - 10%: memory
     * - 20%: benchmark
     * - 30%: total seconds based on max iterations
     * - 30%: total iterations based on max seconds
     *
     * Future:
     * -
     * - Include benchmark scores
     *   - 50%: Max concurrency before max response time threshold
     *   - 50%: Average response time based on X threads (config.max_threads)
     *   - Maybe 20%
     */
    protected function getScore(
        array &$statsBaseline,
        array &$statsBefore,
        array &$statsAttempt,
        ?int $precision = null
    ): float {
        if (is_null($precision)) {
            $precision = $this->defaultPericision;
        }

        // Score math
        $scores = [];

        $scores['line_count'] = percentage_difference_fixed(
            $statsBaseline['line_count'],
            $statsBefore['line_count'],
            $statsAttempt['line_count'] == 0
                ? $statsAttempt['line_count']
                : 0.00000001
        );

        // TODO: To be figured out
        $scores['memory'] = percentage_difference_fixed(0, 100, 100);

        $scores['duration'] = percentage_difference_fixed(
            $statsBaseline['duration'],
            $statsBefore['duration'],
            $statsAttempt['duration']
        );

        $scores['iterations'] = percentage_difference_fixed(
            $statsBaseline['iterations'],
            $statsBefore['iterations'],
            $statsAttempt['iterations']
        );

        $score = array_sum([
            ($scores['line_count'] * 0.1) +
            ($scores['memory'] * 0.1) +
            ($scores['duration'] * 0.4) +
            ($scores['iterations'] * 0.4)
        ]);

        return round($score, $precision);
    }

    protected function toReportRow(
        KataChallengeResultObject $resultBefore,
        KataChallengeResultObject $resultAttempt,
    ): array {
        $resultBaseline = $this->runChallengeMethod($resultBefore->getBaselineReflectionMethod());

        // Get stats
        $statsBaseline = $resultBaseline->getStats();
        $statsBefore = $resultBefore->getStats();
        $statsAttempt = $resultAttempt->getStats();

        $scoreBaseline = $this->getScore(
            $statsBaseline,
            $statsBaseline,
            $statsBaseline
        );

        $scoreBefore = $this->getScore(
            $statsBaseline,
            $statsBefore,
            $statsBefore
        );

        $scoreAttempt = $this->getScore(
            $statsBaseline,
            $statsBefore,
            $statsAttempt
        );

        if ($scoreAttempt > $scoreBaseline) {
            throw new Exception(sprintf(
                'Slower than the baseline: %s->%s is slower than %s->%s with %d ms',
                $resultBefore->getClassName(),
                $resultBefore->getMethodName(),
                $resultAttempt->getClassName(),
                $resultAttempt->getMethodName(),
                $resultAttempt->getReflectionMethod()->class
            ));
        }

        print_r([
            'scoreBaseline' => $scoreBaseline,
            'scoreBefore' => $scoreBefore,
            'scoreAttempt' => $scoreAttempt,
        ]);

        return [
            'scoreBaseline' => $scoreBaseline,
            'scoreBefore' => $scoreBefore,
            'scoreAttempt' => $scoreAttempt,
        ];

        dd([
            'score' => $score
        ]);

        $stats = [
            'baseline' => $resultBaseline->getStats(),
            'before' => $resultBefore->getStats(),
            'attempt' => $resultAttempt->getStats(),
        ];


        dd([
            'stats' => $stats,
            'scores' => $scores
        ]);

        $baselineDuration = $resultBaseline->getDuration();
        $baselineIterations = $resultBaseline->getIterations();

        $beforeDuration = $resultBefore->getDuration();
        $attemptDuration = $resultAttempt->getDuration();

        $beforeIterations = $resultBefore->getIterations();
        $attemptIterations = $resultAttempt->getIterations();

        $beforeMd5 = $resultBefore->getOutputsMd5();
        $attemptMd5 = $resultBefore->getOutputsMd5();

        // Scale by baseline
        $beforeDurationRel = $beforeDuration - $baselineDuration;
        $attemptDurationRel = $attemptDuration - $baselineDuration;
        $beforeIterationsRel = $baselineIterations - $beforeIterations;
        $attemptIterationsRel = $baselineIterations - $attemptIterations;

        return [
            $resultBefore->getClassName(),
            $resultBefore->getMethodName(),
            sprintf("%s\n%s", "baseline", "before", "attempt"),
            sprintf("%s\n%s", $beforeDuration,
                $this->wrapInFormat($attemptDuration, $attemptDuration < $beforeDuration)
            ),
            sprintf("%s\n%s", $beforeDurationRel,
                $this->wrapInFormat($attemptDurationRel, $attemptDurationRel < $beforeDurationRel)
            ),
            sprintf("%s\n%s", $beforeIterations,
                $this->wrapInFormat($attemptIterations, $attemptIterations > $beforeIterations)
            ),
            sprintf("%s\n%s", $beforeIterationsRel,
                $this->wrapInFormat($attemptIterationsRel, $attemptIterationsRel > $beforeIterationsRel)
            ),
            sprintf("%s\n%s", $beforeMd5,
                $this->wrapInFormat($attemptMd5, $attemptMd5 === $beforeMd5)
            ),
        ];
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
        if ($mode === KataRunnerMode::ATTEMPT) {
            $classParts = explode('\\', $reflectionMethod->class);
            $className = sprintf('%s%s', array_pop($classParts), self::CHALLENGE_SUFFIX);
            array_push($classParts, $className);
            $targetClass = implode('\\', $classParts);

            // Change reflection method based on the mode
            $reflectionClass = new ReflectionClass($targetClass);
            $reflectionMethod = $reflectionClass->getMethod($reflectionMethod->name);
        }

        if (!class_exists($targetClass)) {
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
            'outputs' => $outputs
        ];
    }

    protected function runChallengeMethodMaxIterations(
        ReflectionMethod $reflectionMethod,
        int $maxIterations
    ): array {
        $outputs = [];

        $bar = $this->command?->getOutput()->createProgressBar($maxIterations);
        $bar->setFormat("%message%\n %current%/%max% [%bar%] %percent:3s%%");
        foreach (range(1, $maxIterations) as $i) {
            $className = $reflectionMethod->class;
            $instance = app($className);
            $methodName = $reflectionMethod->name;
            $bar->setMessage(sprintf(
                'Running: %s->%s(%d)',
                $className,
                $methodName,
                $i
            ));

            $outputs[] = $instance->{$methodName}($i);

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

        $counter = 0;
        do {
            $msLeft = now()->diffInMilliseconds($dateTimeEnd, false);

            $counter++;
            $instance = app($reflectionMethod->class);
            $outputs[] = $instance->{$reflectionMethod->name}($counter);
            $instance = null;

            $bar?->setProgress($msMax - $msLeft);
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
