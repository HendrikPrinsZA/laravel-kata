<?php

namespace App;

use App\Exceptions\KataChallengeException;
use App\Exceptions\KataChallengeProfilingException;
use Illuminate\Http\Request;

class KataChallenge
{
    protected const MEMORY_REAL_USAGE = true;

    protected const MAX_INTERATIONS = null;

    protected const EXPECTED_MODELS = [];

    protected int $maxSeconds = 0;

    protected int $maxIterations = 1;

    protected ?int $memoryUsageStart = null;

    protected ?int $memoryUsageTotal = null;

    public function __construct(protected ?Request $request = null)
    {
        $this->memoryUsageStart = memory_get_usage(self::MEMORY_REAL_USAGE);

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

    public function return(mixed $value): mixed
    {
        $this->captureMemoryUsage();

        return $value;
    }

    public function captureMemoryUsage(): void
    {
        $this->memoryUsageTotal ??= 0;

        $memoryUsageEnd = memory_get_usage(self::MEMORY_REAL_USAGE);
        $memoryUsage = $memoryUsageEnd - $this->memoryUsageStart;

        if ($memoryUsage > 0) {
            $this->memoryUsageTotal += $memoryUsage;
        }

        $this->memoryUsageStart = memory_get_usage(self::MEMORY_REAL_USAGE);
    }

    public function getMemoryUsage(): int
    {
        if (is_null($this->memoryUsageTotal)) {
            throw new KataChallengeProfilingException(sprintf(
                'Memory usage not captured for %s, did you forget to call $this->return()?',
                static::class,
            ));
        }

        return $this->memoryUsageTotal;
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
