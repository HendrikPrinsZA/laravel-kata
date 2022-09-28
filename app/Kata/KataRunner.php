<?php

namespace App\Kata;

use App\Kata\Challenges\ChallengeKataEloquent;
use App\Kata\Challenges\ChallengeKataPhp;
use Clockwork\Request\Timeline\Event;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

class KataRunner
{
    protected const CHALLENGE_SUFFIX = 'Attempt1';

    protected array $kataChallenges = [
        ChallengeKataEloquent::class,
        ChallengeKataPhp::class,
    ];

    public function __construct(
        protected ?Command $command,
        protected ?int $maxIterations,
        protected ?string $mode = null
    ) {
        if (!isset($this->mode)) {
            $this->mode = $this->command?->option('mode') ?? 'all';
        }
    }

    public function run(): Collection
    {
        $results = collect();

        foreach ($this->kataChallenges as $kataChallenge) {
            $results->push([
                'kataChallenge' => $kataChallenge,
                'results' => $this->handleChallenge($kataChallenge)
            ]);
        }

        return $results;
    }

    protected function handleChallenge(string $kataChallenge): Collection
    {
        $results = collect();
        $kataChallengeReflection = new ReflectionClass($kataChallenge);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            $results->push($this->handleChallengeMethod($reflectionMethod));
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
     *
     * Returns true when
     *  1. Results match
     *  2. Is faster
     */
    protected function handleChallengeMethod(ReflectionMethod $reflectionMethod): array|bool
    {
        $docComment = $reflectionMethod->getDocComment();
        $docComment = collect(explode("\n", $docComment))
            ->map(fn($line) => trim($line))
            ->splice(1, -1)
            ->map(fn($line) => str_replace('* ', '', $line))
            ->join("\n");

        $classParts = explode('\\', $reflectionMethod->class);
        $className = array_pop($classParts);
        $method = sprintf('%s->%s()', $className, $reflectionMethod->name);

        // initialize
        $outputBefore = null;
        $outputBeforeMd5 = '';
        $eventBeforeDuration = 0;
        $outputAfter = null;
        $outputAfterMd5 = '';
        $eventAfterDuration = 0;

        if ($this->mode !== 'after') {
            /** @var Event $eventBefore */
            $eventBefore = clock()->event(sprintf('%s:before', $method))->color('green')->begin();
            $outputBefore = $this->runChallengeMethod($reflectionMethod);
            $eventBefore->end();

            $outputBeforeMd5 = md5(json_encode($outputBefore));
            $eventBeforeDuration = round($eventBefore?->duration() ?? 0, 2);
        }

        if ($this->mode !== 'before') {
            /** @var Event $eventAfter */
            $eventAfter = clock()->event(sprintf('%s:after', $method))->color('green')->begin();
            $outputAfter = $this->runChallengeMethod($reflectionMethod, 'after');
            $eventAfter->end();

            $outputAfterMd5 = md5(json_encode($outputAfter));
            $eventAfterDuration = round($eventAfter?->duration() ?? 0, 2);
        }

        $this->command?->table([
            'Key', 'Value'
        ], [
            [ 'Method', $method ],
            [ 'Path', help_me_code($reflectionMethod) ],
            [ 'Doc Comment', $docComment ],
            [ 'Before / Output (md5)',  $outputBeforeMd5],
            [ 'Before / Duration (ms)', $eventBeforeDuration ],
            [ 'After / Output (md5)', $this->wrap_in_format($outputAfterMd5, $outputAfterMd5 === $outputBeforeMd5) ],
            [ 'After / Duration (ms)', $this->wrap_in_format($eventAfterDuration, $eventAfterDuration < $eventBeforeDuration) ],
        ]);

        if ($outputBeforeMd5 !== $outputAfterMd5) {
            $this->command?->warn(sprintf(
                'Expected output md5 of "%s", but found: "%s"',
                $outputBeforeMd5,
                $outputAfterMd5
            ));
        }

        if ($eventAfterDuration >= $eventBeforeDuration) {
            $this->command?->warn(sprintf(
                'Slower by %s ms',
                $eventAfterDuration - $eventBeforeDuration,
            ));
        }

        return [
            'method' => $method,
            'before' => [
                'outputMd5' => $outputBeforeMd5,
                'duration' => $eventBeforeDuration
            ],
            'after' => [
                'outputMd5' => $outputAfterMd5,
                'duration' => $eventAfterDuration
            ],
        ];
    }

    protected function wrap_in_format(string $string, bool $success): string {
        return $success
            ? sprintf('<fg=green>%s</>', $string)
            : sprintf('<fg=red>%s</>', $string);

        $el = $success
            ? 'info'
            : 'warn';

        return sprintf('<%s>%s</%s>', $el, $string, $el);
    }

    protected function runChallengeMethod(ReflectionMethod $reflectionMethod, string $mode = 'before'): array
    {
        if (!in_array($mode, ['before', 'after'])) {
            throw new Exception(sprintf('Unexpected mode "%s"', $mode));
        }

        $targetClass = $reflectionMethod->class;
        if ($mode === 'after') {
            $classParts = explode('\\', $reflectionMethod->class);
            $className = sprintf('%s%s', array_pop($classParts), self::CHALLENGE_SUFFIX);
            array_push($classParts, $className);
            $targetClass = implode('\\', $classParts);
        }

        if (!class_exists($targetClass)) {
            throw new Exception(sprintf('Class not found %s', $targetClass));
        }

        $outputs = [];
        $limit = $this->maxIterations;
        $bar = $this->command?->getOutput()->createProgressBar($limit);

        foreach (range(1, $limit) as $i) {
            $instance = app($targetClass);
            $outputs[] = $instance->{$reflectionMethod->name}($i);
            $bar?->advance();

            $instance = null;
        }

        $bar?->finish();
        $this->command?->newLine();
        return $outputs;
    }
}
