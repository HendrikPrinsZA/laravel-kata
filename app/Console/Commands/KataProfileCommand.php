<?php

namespace App\Console\Commands;

use App\Challenges\A\Sample;
use Illuminate\Console\Command;

class KataProfileCommand extends Command
{
    protected $signature = 'kata:profile {challenge} {method} {iteration=1}';

    protected $description = 'Kata profile challenge method';

    public function handle(): int
    {
        $challenge = $this->argument('challenge');
        $method = $this->argument('method');
        $iteration = (int) $this->argument('iteration');

        $challengeA = str_replace('Sample', $challenge, Sample::class);
        $challengeB = str_replace('\\A\\', '\\B\\', $challengeA);
        $instanceA = app()->make($challengeA);
        $instanceB = app()->make($challengeB);

        $returnsA = $instanceA->{$method}($iteration);
        $returnsB = $instanceB->{$method}($iteration);

        $rows = [];
        $rows[] = [
            'returns',
            json_encode($returnsA, JSON_PRETTY_PRINT),
            json_encode($returnsB, JSON_PRETTY_PRINT),
        ];

        $this->info(sprintf('Returns', $challengeA));
        $this->table([
            '#', 'A', 'B',
        ], $rows);

        return self::SUCCESS;
    }
}
