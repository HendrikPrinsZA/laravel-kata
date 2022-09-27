<?php

namespace App\Kata;

use App\Kata\Challenges\ChallengeKataEloquent;
use App\Kata\Challenges\ChallengeKataPhp;
use Clockwork\Request\Timeline\Event;
use Exception;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

class KataRunner
{
    protected const CHALLENGE_SUFFIX = 'Attempt1';

    protected const MAX_ITERATIONS = 1000;

    protected array $kataChallenges = [
        ChallengeKataEloquent::class,
        ChallengeKataPhp::class,
    ];

    public function __construct(protected Command $command) { }

    public function run(): void
    {
        foreach ($this->kataChallenges as $kataChallenge) {
            $this->handleChallenge($kataChallenge);
        }
    }

    protected function handleChallenge(string $kataChallenge): void
    {
        $kataChallengeReflection = new ReflectionClass($kataChallenge);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            $this->handleChallengeMethod($reflectionMethod);
        }
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
    protected function handleChallengeMethod(ReflectionMethod $reflectionMethod): bool
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

        /** @var Event $eventBefore */
        $eventBefore = clock()->event(sprintf('%s:before', $method))->color('green')->begin();
        $outputBefore = $this->runChallengeMethod($reflectionMethod);
        $eventBefore->end();

        /** @var Event $eventAfter */
        $eventAfter = clock()->event(sprintf('%s:after', $method))->color('green')->begin();
        $outputAfter = $this->runChallengeMethod($reflectionMethod, 'after');
        $eventAfter->end();

        $eventBeforeDuration = round($eventBefore->duration(), 2);
        $outputBeforeMd5 = md5(json_encode($outputBefore));
        $eventAfterDuration = round($eventAfter->duration(), 2);
        $outputAfterMd5 = md5(json_encode($outputAfter));

        $this->command->table([
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
            $this->command->warn(sprintf(
                'Expected output md5 of "%s", but found: "%s"',
                $outputBeforeMd5,
                $outputAfterMd5
            ));
            return false;
        }

        if ($eventAfterDuration >= $eventBeforeDuration) {
            $this->command->warn(sprintf(
                'Slower by %s ms',
                $eventAfterDuration - $eventBeforeDuration,
            ));

            return false;
        }

        return true;
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
        $limit = self::MAX_ITERATIONS;
        $bar = $this->command->getOutput()->createProgressBar($limit);

        foreach (range(1, $limit) as $i) {
            $instance = app($targetClass);
            $outputs[] = $instance->{$reflectionMethod->name}($i);
            $bar->advance();

            $instance = null;
        }

        $bar->finish();
        $this->command->newLine();
        return $outputs;
    }
}
