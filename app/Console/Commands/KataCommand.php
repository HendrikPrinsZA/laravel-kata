<?php

namespace App\Console\Commands;

use App\Kata\KataRunner;
use Illuminate\Console\Command;

class KataCommand extends Command
{
    protected const MAX_ITERATIONS = 100;

    protected $signature = 'command:kata {--mode=all}';

    protected $description = 'Kata command POC';

    protected KataRunner $kataRunner;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->kataRunner = app(KataRunner::class, [
            'command' => &$this,
            'maxIterations' => self::MAX_ITERATIONS,
        ]);

        $this->kataRunner->run();

        return 0;
    }
}
