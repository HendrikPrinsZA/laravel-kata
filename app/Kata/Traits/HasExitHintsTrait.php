<?php

namespace App\Kata\Traits;

use Exception;
use Illuminate\Support\Collection;

trait HasExitHintsTrait
{
    protected ?Collection $exitHints = null;

    public function __destruct()
    {
        if (is_null($this->command)) {
            return;
        }

        if (is_null($this->exitHints) || empty($this->exitHints)) {
            return;
        }

        if (!config('laravel-kata.show_hints')) {
            return;
        }

        $randomHint = $this->exitHints->random(1)->first();

        $this->command->warn($randomHint);
        if (!$this->command->confirm('Continue?', true)) {
            throw new Exception('Cheers!');
        }
    }

    protected function addExitHints(array $messages): void
    {
        if (is_null($this->exitHints)) {
            $this->exitHints = collect();
        }

        $this->exitHints->push(...$messages);
    }
}
