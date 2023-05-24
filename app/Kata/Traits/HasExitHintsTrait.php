<?php

namespace App\Kata\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

trait HasExitHintsTrait
{
    protected ?bool $showHintsExtended = null;

    protected ?Collection $exitHints = null;

    public function __destruct()
    {
        if (app()->runningUnitTests()) {
            return;
        }

        if (! isset($this->command) || is_null($this->command)) {
            return;
        }

        if (! Config::get('laravel-kata.show-hints')) {
            return;
        }

        if (is_null($this->exitHints) || empty($this->exitHints)) {
            $this->command->info('No hints found!');

            return;
        }

        $this->command->warn($this->getRandomExitHint());
    }

    protected function getExitHints(): Collection
    {
        return $this->exitHints;
    }

    protected function getRandomExitHint(): string
    {
        return (string) $this->exitHints->random(1)->first();
    }

    protected function addExitHintsFromViolations(array $violations): void
    {
        if (is_null($this->showHintsExtended)) {
            $this->showHintsExtended = Config::get('laravel-kata.show-hints-extended', false);
        }

        $hintKeys = [
            'class',
            'method',
            'function',
            'beginLine',
            'endLine',
        ];

        foreach ($violations as $violation) {
            $hint = $this->showHintsExtended
                ? sprintf('(i) %s', json_encode(array_subset_by_keys($violation, $hintKeys)))
                : '(i) Check config to show extended hints';

            $message = sprintf(
                "### %s (%s)\n%s\n\n%s\n\n%s",
                $violation['ruleSet'],
                $violation['rule'],
                $violation['description'],
                $violation['externalInfoUrl'] === '#' ? '' : $violation['externalInfoUrl'],
                $hint
            );

            $this->addExitHint($message);
        }
    }

    private function addExitHint(string $message): void
    {
        if (is_null($this->exitHints)) {
            $this->exitHints = collect();
        }

        $this->exitHints->push($message);
    }
}
