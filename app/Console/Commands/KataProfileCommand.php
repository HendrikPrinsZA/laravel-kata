<?php

namespace App\Console\Commands;

use App\Challenges\A\Sample;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

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

        if (confirm(sprintf('Ready to run %s->%s(%d)?', $challengeA, $method, $iteration))) {
            $responseA = $instanceA->{$method}($iteration);

            $this->info('Response of A');
            $this->comment(json_encode($responseA, JSON_PRETTY_PRINT));
        } else {
            $this->error(sprintf('Skipped %s->%s(%d)', $challengeA, $method, $iteration));
        }

        if (confirm(sprintf('Ready to run %s->%s(%d)?', $challengeB, $method, $iteration))) {
            $responseB = $instanceB->{$method}($iteration);

            $this->info('Response of B');
            $this->comment(json_encode($responseB, JSON_PRETTY_PRINT));
        } else {
            $this->error(sprintf('Skipped %s->%s(%d)', $challengeB, $method, $iteration));
        }

        return self::SUCCESS;
    }
}
