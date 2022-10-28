<?php

namespace App\Kata;

use Illuminate\Http\Request;

class KataChallenge
{
    protected int $maxSeconds = 0;

    protected int $maxIterations = 1;

    public function __construct(protected ?Request $request = null)
    {
        $this->maxSeconds = $this->request?->get('max-seconds') ?? config(
            'laravel-kata.max-seconds',
            $this->maxSeconds
        );

        $this->maxIterations = $this->request?->get('max-iterations') ?? config(
            'laravel-kata.max-iterations',
            $this->maxIterations
        );

        $this->setUp();
    }

    public function getMaxSeconds(): int
    {
        return $this->maxSeconds;
    }

    public function getMaxIterations(): int
    {
        return $this->maxIterations;
    }

    public function baseline(): void
    {
        // Want at least 1 line, even if it is just a comment
    }

    protected function setUp(): void
    {
    }
}
