<?php

namespace App\Kata;

use App\Kata\Challenges\ChallengeKataEloquent;
use Clockwork\Request\Timeline\Event;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

class KataRunner
{
    protected const MAX_ITERATIONS = 100;

    protected array $kataChallenges = [
        ChallengeKataEloquent::class
    ];

    public function __construct(protected Command $command) { }

    public function run(): void
    {
        foreach ($this->kataChallenges as $kataChallenge) {
            $this->runChallenge($kataChallenge);
        }
    }

    protected function runChallenge(string $kataChallenge): void
    {
        $kataChallengeReflection = new ReflectionClass($kataChallenge);

        /** @var ReflectionMethod $reflectionMethod */
        foreach ($kataChallengeReflection->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->getModifiers() !== ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            $this->runChallengeMethod($reflectionMethod);
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
    protected function runChallengeMethod(ReflectionMethod $reflectionMethod): void
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

        /** @var Event $event */
        $outputs = [];
        $event = clock()->event(sprintf('%s:before', $method))
            ->color('green')
            ->begin();

        $limit = self::MAX_ITERATIONS;
        $bar = $this->command->getOutput()->createProgressBar($limit);
        foreach (range(1, $limit) as $i) {
            $instance = app($reflectionMethod->class);
            $outputs[] = $instance->{$reflectionMethod->name}($i);
            $bar->advance();
        }
        $event->end();
        $bar->finish();
        $this->command->newLine();

        $this->command->table([
            'Key', 'Value'
        ], [
            [
                'Method',
                $method
            ],
            [
                'Path',
                help_me_code($reflectionMethod)
            ],
            [
                'Doc Comment',
                $docComment
            ],
            [
                'Output (md5)',
                md5(json_encode($outputs)), // json_encode($outputs, JSON_PRETTY_PRINT)
            ],
            [
                'Before (ms)',
                $event->duration()
            ]
        ]);
    }
}
