<?php

namespace App\Kata\Utilities;

class PerformanceUtility
{
    private function __construct(
        protected int $count = 0,
        protected int $memoryUsageSum = 0,
        protected float $executionTimeSum = 0
    ) {
    }

    public static function make()
    {
        return new self();
    }

    public function reset(): void
    {
        $this->count = 0;
        $this->memoryUsageSum = 0;
        $this->executionTimeSum = 0;
    }

    public function run(callable $callable): mixed
    {
        $startTime = microtime(true);
        $memoryUsageBase = memory_get_usage(false);

        $this->count++;
        $output = $callable();

        $memoryUsage = memory_get_usage(false) - $memoryUsageBase;

        if ($memoryUsage < 0) {
            $memoryUsage = 0;
        }

        $this->memoryUsageSum += $memoryUsage;
        $this->executionTimeSum += microtime(true) - $startTime;

        return $output;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getMemoryUsageSum(): int
    {
        return $this->memoryUsageSum;
    }

    public function getMemoryUsageAvg(): float
    {
        return $this->memoryUsageSum / $this->count;
    }

    public function getExecutionTimeSum(): float
    {
        return $this->executionTimeSum;
    }

    public function getExecutionTimeAvg(): float
    {
        return $this->executionTimeSum / $this->count;
    }
}
