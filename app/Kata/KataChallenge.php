<?php

namespace App\Kata;

class KataChallenge implements KataChallengeInterface
{
    protected int $maxSeconds;

    protected int $maxIterations;

    public function __construct()
    {
        if (!isset($this->maxSeconds)) {
            $this->maxSeconds = config('laravel-kata.max_seconds');
        }

        if (!isset($this->maxIterations)) {
            $this->maxIterations = config('laravel-kata.max_iterations');
        }
    }

    public function getMaxSeconds(): int
    {
        return $this->maxSeconds;
    }

    public function getMaxIterations(): int
    {
        return $this->maxIterations;
    }

    public function baseline(): void {
        // Want at least 1 line, even if it is just a comment
    }
}
