<?php

namespace App\Kata;

abstract class KataChallenge
{
    /**
     * Override the default maximum seconds
     */
    protected int $maxSeconds;

    /**
     * Override the default maximum iterations
     */
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
}
