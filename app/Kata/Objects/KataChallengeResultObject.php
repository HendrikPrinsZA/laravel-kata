<?php

namespace App\Kata\Objects;

use App\Kata\Enums\KataRunnerIterationMode;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionClass;
use ReflectionMethod;

class KataChallengeResultObject extends JsonResource
{
    public function __construct(
        protected ReflectionMethod &$reflectionMethod,
        protected array $result
    ) {
        parent::__construct($result);
    }

    public function getReflectionMethod(): ReflectionMethod
    {
        return $this->reflectionMethod;
    }

    public function getBaselineReflectionMethod(): ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($this->reflectionMethod->class);
        return $reflectionClass->getMethod('baseline');
    }

    public function getStats(): array
    {
        return [
            'duration' => $this->getDuration(),
            'iterations' => $this->getIterations(),
            'outputs_md5' => $this->getOutputsMd5(),
            'line_count' => $this->reflectionMethod->getEndLine() - $this->reflectionMethod->getStartLine()
        ];
    }

    public function getDuration(
        KataRunnerIterationMode $kataRunnerIterationMode = KataRunnerIterationMode::MAX_ITERATIONS,
    ): float {
        return $this->result[$kataRunnerIterationMode->value]['duration'];
    }

    public function getIterations(
        KataRunnerIterationMode $kataRunnerIterationMode = KataRunnerIterationMode::MAX_SECONDS,
    ): int {
        return $this->result[$kataRunnerIterationMode->value]['outputs_count'];
    }

    public function getOutputsMd5(
        KataRunnerIterationMode $kataRunnerIterationMode = KataRunnerIterationMode::MAX_ITERATIONS,
    ): string {
        return $this->result[$kataRunnerIterationMode->value]['outputs_md5'];
    }

    public function getClassName(): string
    {
        $classParts = explode('\\', $this->reflectionMethod->class);
        return array_pop($classParts);
    }

    public function getMethodName(): string
    {
        return $this->reflectionMethod->name;
    }
}
