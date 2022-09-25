<?php

namespace App\Console\Commands;

use App\Kata\Challenges\ChallengeKataEloquent;
use App\Kata\Challenges\MyChallengeKataEloquent;
use App\Kata\KataRunner;
use App\Objects\ClockworkEventResponse;
use App\Utilities\FiberThread;
use Clockwork\Clockwork;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ReflectionClass;

class KataCommand extends Command
{
    protected const MAX_ITERATIONS = 100;

    protected const KATAS = [
        ChallengeKataEloquent::class
    ];

    protected $signature = 'command:kata';

    protected $description = 'Kata command POC';

    protected Clockwork $clock;

    protected KataRunner $kataRunner;

    public function __construct()
    {
        parent::__construct();

        $this->kataRunner = app(KataRunner::class, [
            'command' => &$this
        ]);
    }

    public function handle()
    {
        $this->kataRunner->run();
    }

    public function handleOG()
    {
        $this->clock = clock();

        $this->info('Creating cart');
        $this->createCart();

        $this->info('Handling in sequence');
        $this->handleSequence();

        // No real benefit
        // $this->info('Handling in parallel');
        // $this->handleParallel();
    }

    protected function createCart()
    {
        // See https://underground.works/clockwork/#docs-client-metrics
        $cart = $this->clock->userData('cart')
            ->title('Report');

        $cart->counters([
            'Tests' => 1,
            'Duration (before)' => 1000,
            'Duration (after)' => 10,
        ]);

        $cart->table('Tests', [
            [
                'Run' => 'Before',
                'Duration (ms)' => 1000,
                'Database duration (ms)' => 1000,
            ],
            [
                'Run' => 'After',
                'Duration (ms)' => 10,
                'Database duration (ms)' => 1,
            ]
        ]);
    }

    protected function handleParallel()
    {
        FiberThread::register($this, 'before');
        FiberThread::register($this, 'after');

        $outputs = FiberThread::run();

        $this->table([
            'Iteration', 'Response', 'Duration (ms)'
        ], [
            [
                'Before',
                $outputs['before']->response() ?? 'N/A',
                $outputs['before']->duration() ?? 'N/A',
                $outputs['before']->queries()->count() ?? 'N/A',
            ],
            [
                'After',
                $outputs['after']->response() ?? 'N/A',
                $outputs['after']->queries()->count() ?? 'N/A',
            ]
        ]);

        return 0;
    }

    public function handleSequence()
    {
        DB::connection()->raw('SET GLOBAL query_cache_type=OFF;');

        $before = $this->before();
        $after = $this->after();

        $this->table([
            'Iteration', 'Response', 'Duration (ms)', 'Database duration (ms)'
        ], [
            [
                'Before',
                $before->response(),
                $before->duration(),
                '?'
            ],
            [
                'After',
                $after->response(),
                $after->duration(),
                '?'
            ]
        ]);

        return 0;
    }


    public function before(): ClockworkEventResponse
    {
        $this->info('Running before');

        /** @var ChallengeKataEloquent $challengeKataEloquent */
        $challengeKataEloquent = app(ChallengeKataEloquent::class);

        $result = 0;
        $event = $this->clock->event('before')->color('green')->begin();
        $limit = self::MAX_ITERATIONS;
        $bar = $this->output->createProgressBar($limit);
        foreach (range(1, $limit) as $i) {
            $result += $challengeKataEloquent->aggregates($i);
            $bar->advance();
        }
        $event->end();
        $bar->finish();
        $this->info("\nDone");

        return new ClockworkEventResponse($this->clock, $event, $result);
    }

    public function after(): ClockworkEventResponse
    {
        $this->info('Running after');

        $namespace = ChallengeKataEloquent::class;
        $namespaceParts = explode('\\', $namespace);
        $className = array_pop($namespaceParts);
        $targetClassName = sprintf('My%s', $className);
        $namespaceParts[] = $targetClassName;
        $targetNamespace = implode('\\', $namespaceParts);

        // If not set fall back to base, send a flag
        //
        // TODO:
        //    - Create file if not exists, then open in editor
        //    - Prompt user to (a) retry, (b) reset, (c) edit, (d) skip
        if (!class_exists($targetNamespace)) {
            $reflector = new ReflectionClass($namespace);
            $sourceFilePath = $reflector->getFileName();
            $targetFilePath = str_replace($className, $targetClassName, $sourceFilePath);
            $sourceContents = file_get_contents($sourceFilePath);

            // Rename the class and extend the base
            $search = sprintf(
                'class %s',
                $className
            );
            $replace = sprintf(
                'class %s extends %s',
                $targetClassName,
                $className
            );
            $targetContents = str_replace($search, $replace, $sourceContents);

            if (!file_put_contents($targetFilePath, $targetContents)) {
                throw new Exception(sprintf(
                    "Failed to create new file: %s (check permissions) with content: {\n%s\n}",
                    $targetFilePath,
                    $targetContents
                ));
            }

            $this->info(sprintf(
                "New challenge created, go try it out!\n\nFile/s:\n - %s",
                str_replace('/var/www/html/', '', $targetFilePath)
            ));

            // Note: This doesn't really work, need to reload the php instance
            $this->confirm('Enter when ready...');
            require_once $targetFilePath;
            return $this->after();
        }

        /** @var ChallengeKataEloquent $challengeKataEloquent */
        $myChallengeKataEloquent = app($targetNamespace);

        // Check if fn exists...
        // - if not show warning
        $result = 0;
        $event = $this->clock->event('after')->color('green')->begin();
        $limit = self::MAX_ITERATIONS;
        $bar = $this->output->createProgressBar($limit);
        foreach (range(1, $limit) as $i) {
            $result += $myChallengeKataEloquent->aggregates($i);
            $bar->advance();
        }
        $event->end();
        $bar->finish();
        $this->info("\nDone");

        return new ClockworkEventResponse($this->clock, $event, $result);
    }
}
