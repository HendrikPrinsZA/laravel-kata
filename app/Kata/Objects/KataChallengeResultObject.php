<?php

namespace App\Kata\Objects;

use App\Kata\Enums\KataRunnerIterationMode;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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

    protected function getViolations(): array
    {
        $process = new Process([
            'bin/complexity.sh',
            $this->reflectionMethod->getFileName(),
        ]);

        $process->run();

        if (
            !$process->isSuccessful() &&
            $process->getExitCode() !== 2 // [ignore] Exit Code: 2 (Misuse of shell builtins)
        ) {
            throw new ProcessFailedException($process);
        }

        $output = json_decode($process->getOutput(), true);

        $violations = collect();
        foreach ($output['files'] as $file) {
            foreach ($file['violations'] as $violation) {
                $violations->push($violation);
            }
        }

        return $violations->toArray();
    }

    public function getStatsAsText(): string
    {
        $stats = $this->getStats();

        $stats['violations'] = count($stats['violations']);

        $keys = [
            'line_count',
            'violations',
            'duration',
            'iterations',
        ];

        $lines = [];
        foreach ($keys as $key) {
            $lines[] = $stats[$key] ?? 'N/A';
        }

        return implode("\n", $lines);
    }

    public function getStats(): array
    {
        return [
            'duration' => $this->getDuration(),
            'iterations' => $this->getIterations(),
            'outputs_md5' => $this->getOutputsMd5(),
            'violations' => $this->getViolations(),
            'line_count' => $this->reflectionMethod->getEndLine() - $this->reflectionMethod->getStartLine(),
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
