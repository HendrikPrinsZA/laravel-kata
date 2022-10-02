<?php

namespace App\Console\Commands;

use App\Kata\KataRunner;
use Illuminate\Console\Command;

class KataCommand extends Command
{
    protected $signature = 'command:kata {--mode=all}';

    protected $description = 'Kata command POC';

    protected KataRunner $kataRunner;

    public function handle(): int
    {
        $this->kataRunner = app(KataRunner::class, [ 'command' => &$this ]);
        $this->kataRunner->run();
        return 0;
    }
}
