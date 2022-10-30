<?php

namespace App\Console\Commands;

use App\Kata\Exceptions\KataChallengeScoreException;
use App\Kata\KataRunner;
use Illuminate\Console\Command;

class KataCommand extends Command
{
    protected $signature = 'kata:run {--mode=all}';

    protected $description = 'Kata command POC';

    protected KataRunner $kataRunner;

    public function handle(): int
    {
        $this->kataRunner = app(KataRunner::class, [
            'command' => &$this,
            'failOnScore' => true,
        ]);

        try {
            $this->kataRunner->run();
        } catch (KataChallengeScoreException $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        return 0;
    }
}
