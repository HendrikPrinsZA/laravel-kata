<?php

namespace App\Kata;

use App\Exceptions\KataChallengeException;
use Illuminate\Http\Request;

class KataChallenge
{
    protected const MAX_INTERATIONS = null;

    protected const EXPECTED_MODELS = [];

    protected int $maxSeconds = 0;

    protected int $maxIterations = 1;

    public function __construct(protected ?Request $request = null)
    {
        $this->maxSeconds = $this->request?->get('max-seconds') ?? config(
            'laravel-kata.max-seconds',
            $this->maxSeconds
        );

        $this->maxIterations = $this->request?->get('max-iterations')
            ?? static::MAX_INTERATIONS
            ?? config(
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
        foreach (static::EXPECTED_MODELS as $expectedModelClass) {
            if ($expectedModelClass::count() === 0) {
                throw new KataChallengeException(sprintf(
                    'Expected records in %s, but found none',
                    $expectedModelClass)
                );
            }
        }
    }
}
