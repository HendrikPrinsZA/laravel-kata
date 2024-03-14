<?php

namespace App\Console\Commands;

use App\Exceptions\KataChallengeScoreException;
use App\KataRunner;
use Illuminate\Console\Command;
use Larawell\LaravelPlus\Console\Commands\Traits\SmartChoice;

class KataRunCommand extends Command
{
    use SmartChoice;

    protected $signature = 'kata:run {--all} {--challenge=*} {--method=*}';

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
                fn (string $challenge) => sprintf('App\\Challenges\\A\\%s', $challenge)
            )->toArray();

            return $this->handleRun($challenges);
        }

        $classNames = collect(config('laravel-kata.challenges'))->map(
            fn ($namespace) => str_replace('App\\Challenges\\A\\', '', $namespace)
        )->toArray();

        $classNames = $this->smartChoice('Challenges', $classNames);

        $challenges = collect($classNames)->map(
            fn ($className) => sprintf('App\\Challenges\\A\\%s', $className)
        )->toArray();

        return $this->handleRun($challenges);
    }

    protected function handleRun(array $challenges = []): int
    {
        $configChallenges = config('laravel-kata.challenges');

        $challenges = ! empty($challenges) ? $challenges : $configChallenges;
        $methods = $this->option('method');

        $this->kataRunner = app()->makeWith(KataRunner::class, [
            'command' => $this,
            'challenges' => $challenges,
            'methods' => $methods,
        ]);

        try {
            $this->kataRunner->run();
        } catch (KataChallengeScoreException $exception) {
            if (app()->runningUnitTests()) {
                throw $exception;
            }

            $warning = sprintf('%s', $exception->getMessage());
            if (in_array($exception::class, config('laravel-kata.ignore-exceptions'))) {
                $this->warn($warning);

                return self::SUCCESS;
            }

            $this->error($warning);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
