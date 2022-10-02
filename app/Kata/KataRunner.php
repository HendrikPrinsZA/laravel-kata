<?php

namespace App\Kata;

use App\Kata\Challenges\KataChallengeEloquent;
use App\Kata\Challenges\KataChallengePhp;
use App\Kata\Challenges\KataChallengeSample;
use App\Kata\Enums\KataRunnerIterationMode;
use App\Kata\Enums\KataRunnerMode;
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

    protected array $kataChallenges = [
        KataChallengeSample::class,
        KataChallengeEloquent::class,
        KataChallengePhp::class,
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
    }

    public function run(): Collection
    {
        $results = collect();

        foreach ($this->kataChallenges as $kataChallenge) {
            $results->push([
                'class' => $kataChallenge,
                'results' => $this->handleChallenge($kataChallenge)
            ]);
        }

        $this->report($results);

        return $results;
    }

    protected function report(Collection $results): void
    {
        $rows = [];
        foreach ($results as $result) {
            $classResults = $result['results'];

            foreach ($classResults as $method => $classResult) {
                // TODO: Move to filter before
                // - Something bad with dynamic class methods
                if (!$classResult) { continue; }

                $beforeDuration = round($classResult['before']['max-iterations']['duration']);
                $attemptDuration = round($classResult['attempt']['max-iterations']['duration']);

                $beforeIterations = $classResult['before']['max-seconds']['outputs_count'];
                $attemptIterations = $classResult['attempt']['max-seconds']['outputs_count'];

                $beforeMd5 = $classResult['before']['max-iterations']['outputs_md5'];
                $attemptMd5 = $classResult['attempt']['max-iterations']['outputs_md5'];

                $classParts = explode('\\', $result['class']);
                $className = array_pop($classParts);

                $rows[] = [
                    $className,
                    $method,
                    sprintf("%s\n%s", "before", "attempt"),
                    sprintf("%s\n%s", $beforeDuration,
                        $this->wrapInFormat($attemptDuration, $attemptDuration < $beforeDuration)
                    ),
                    sprintf("%s\n%s", $beforeIterations,
                        $this->wrapInFormat($attemptIterations, $attemptIterations > $beforeIterations)
                    ),
                    sprintf("%s\n%s", $beforeMd5,
                        $this->wrapInFormat($attemptMd5, $attemptMd5 === $beforeMd5)
                    ),
                ];

                // Blank row to split
                $rows[] = [''];
            }
        }

        $this->command->table([
            'Challenge',
            'Method',
            'Run',
            'Duration',
            'Iterations',
            'Output md5'
        ], $rows);
    }

    protected function handleChallenge(string $kataChallenge): array
    {
        $return = [];
        $kataChallengeReflection = new ReflectionClass($kataChallenge);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            $return[$reflectionMethod->name] = $this->handleChallengeMethod($reflectionMethod);
        }

        return $return;
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

    protected function runChallengeMethod(ReflectionMethod $reflectionMethod, KataRunnerMode $mode): array
    {
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
        $instance = app($targetClass);
        $maxIterations = $instance->getMaxIterations();
        $maxSeconds = $instance->getMaxSeconds();
        $instance = null;

        $minOutputs = null;
        $return = [];
        foreach ($this->iterationModes as $iterationMode) {
            $challengeOutputs = $this->runChallengeMethodMaxMode(
                $reflectionMethod,
                $iterationMode,
                $maxIterations,
                $maxSeconds,
            );

            // Exception: If zero, should fail!
            if ($minOutputs === 0) {
                throw new Exception(sprintf(
                    'Unexpected empty outputs from %s->%s()',
                    $reflectionMethod->class,
                    $reflectionMethod->name,
                ));
            }

            $return[$iterationMode->value] = [
                'outputs' => $challengeOutputs['outputs'],
                'event' => $challengeOutputs['event'],
                'minOutputs' => $minOutputs
            ];
        }

        // Loop again to separate the concerns
        foreach ($this->iterationModes as $iterationMode) {
            // $iterationModeReturn = $return[$iterationMode->value];
            $return[$iterationMode->value]['outputs_count'] = count($return[$iterationMode->value]['outputs']);
            $return[$iterationMode->value]['outputs_md5'] = md5(json_encode($return[$iterationMode->value]['outputs']));
            $return[$iterationMode->value]['duration'] = $return[$iterationMode->value]['event']->duration();

            // Unset expensive keys
            unset($return[$iterationMode->value]['outputs']);

            // Debugging
            // if (count($return[$iterationMode->value]['outputs']) > 15) {
            //     $return[$iterationMode->value]['outputs'] = array_splice($return[$iterationMode->value]['outputs'], 0, 15);
            //     $return[$iterationMode->value]['outputs'][] = 'Has more...';
            // }
        }

        return $return;
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
        foreach (range(1, $maxIterations) as $i) {
            $instance = app($reflectionMethod->class);
            $outputs[] = $instance->{$reflectionMethod->name}($i);
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

    protected function wrapInFormat(string $string, bool $success): string {
        return $success
            ? sprintf('<fg=green>%s</>', $string)
            : sprintf('<fg=red>%s</>', $string);

        $el = $success
            ? 'info'
            : 'warn';

        return sprintf('<%s>%s</%s>', $el, $string, $el);
    }
}
