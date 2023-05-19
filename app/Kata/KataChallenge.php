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

    protected int $memoryUsageStart;

    protected int $memoryUsageEnd;

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

        $this->memoryUsageStart = memory_get_usage(false);
    }

    public function return(mixed $value): mixed
    {
        $this->captureMemoryUsage();

        return $value;
    }

    public function getMemoryUsage(): int
    {
        if (isset($this->memoryUsageEnd, $this->memoryUsageStart)) {
            return $this->memoryUsageEnd - $this->memoryUsageStart;
        }

        return 0;
    }

    public function captureMemoryUsage(): void
    {
        $this->memoryUsageEnd = memory_get_usage(false);
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
                    $expectedModelClass
                ));
            }
        }
    }
}
