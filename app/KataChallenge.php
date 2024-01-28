<?php

namespace App;

use App\Exceptions\KataChallengeException;
use Illuminate\Http\Request;

class KataChallenge
{
    public const SKIP_VIOLATIONS = true;

    protected const MEMORY_REAL_USAGE = true;

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
            ?? config('laravel-kata.max-iterations', $this->maxIterations);

        if (! is_null(static::MAX_INTERATIONS) && $this->maxIterations > static::MAX_INTERATIONS) {
            $this->maxIterations = static::MAX_INTERATIONS;
        }

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

    protected function setUp(): void
    {
        foreach (static::EXPECTED_MODELS as $expectedModelClass) {
            if ($expectedModelClass::count() === 0) {
                throw new KataChallengeException(sprintf(
                    'Expected records in %s, but found none',
                    $expectedModelClass
                ));
            }
        }
    }
}
