<?php

namespace App\Console\Commands;

use App\Kata\Exceptions\KataChallengeScoreException;
use App\Kata\KataRunner;
use Illuminate\Console\Command;
use Vendors\SmartCommand\Traits\SmartChoice;

class KataRunCommand extends Command
{
    use SmartChoice;

    protected $signature = 'kata:run {--all}';

    protected $description = 'Kata command POC';

    protected KataRunner $kataRunner;

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->handleRun();
        }

        $challenges = $this->smartChoice('Challenges', config('laravel-kata.challenges'));

        return $this->handleRun($challenges);
    }

    protected function handleRun(array $challenges = []): int
    {
        $configChallenges = config('laravel-kata.challenges');

        $challenges = ! empty($challenges) ? $challenges : $configChallenges;

        $this->kataRunner = app()->makeWith(KataRunner::class, [
            'command' => $this,
            'failOnScore' => true,
            'challenges' => $challenges,
        ]);

        try {
            $this->kataRunner->run();
        } catch (KataChallengeScoreException $exception) {
            $this->warn($exception->getMessage());
            $this->error('Score failed!');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
