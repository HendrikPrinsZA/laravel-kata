<?php

namespace App\Console\Commands;

use App\Kata\Exceptions\KataChallengeScoreException;
use App\Kata\KataRunner;
use Illuminate\Console\Command;
use Vendorize\LaravelPlus\Console\Commands\Traits\SmartChoice;

class KataRunCommand extends Command
{
    use SmartChoice;

    protected $signature = 'kata:run {--all} {--challenge=*}';

    protected $description = 'Kata command POC';

    protected KataRunner $kataRunner;

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->handleRun();
        }

        $challenges = $this->option('challenge');
        if (count($challenges) > 0) {
            $challenges = collect($challenges)->map(
                fn (string $challenge) => sprintf('App\\Kata\\Challenges\\A\\%s', $challenge)
            )->toArray();

            return $this->handleRun($challenges);
        }

        $classNames = collect(config('laravel-kata.challenges'))->map(
            fn ($namespace) => str_replace('App\\Kata\\Challenges\\A\\', '', $namespace)
        )->toArray();

        $classNames = $this->smartChoice('Challenges', $classNames);

        $challenges = collect($classNames)->map(
            fn ($className) => sprintf('App\\Kata\\Challenges\\A\\%s', $className)
        )->toArray();

        return $this->handleRun($challenges);
    }

    protected function handleRun(array $challenges = []): int
    {
        $configChallenges = config('laravel-kata.challenges');

        $challenges = ! empty($challenges) ? $challenges : $configChallenges;

        $this->kataRunner = app()->makeWith(KataRunner::class, [
            'command' => $this,
            'challenges' => $challenges,
        ]);

        try {
            $this->kataRunner->run();
        } catch (KataChallengeScoreException $exception) {
            $this->line(wrap_in_format(sprintf('%s', $exception->getMessage()), false));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
