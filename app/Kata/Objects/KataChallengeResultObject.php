<?php

namespace App\Kata\Objects;

use App\Kata\Enums\KataRunnerIterationMode;
use App\Kata\Utilities\CodeUtility;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class KataChallengeResultObject extends JsonResource
{
    public function __construct(
        protected ReflectionMethod $reflectionMethod,
        protected array $result
    ) {
        parent::__construct($result);
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

    public function getCodeSnippet(): string
    {
        return CodeUtility::getCodeSnippet($this->reflectionMethod, 80);
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

    public function getStatsAsText(): string
    {
        $stats = $this->getStats();

        $keys = [
            'line_count',
            'violations_count',
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
        $violations = $this->getViolations();

        return [
            'violations' => $violations,
            'violations_count' => count($violations),

            'duration' => $this->getStat('duration'),
            'iterations' => $this->getStat('outputs_count'),
            'outputs_md5' => $this->getStat('outputs_md5', KataRunnerIterationMode::MAX_ITERATIONS),

            'execution_time_avg' => $this->getExecutionTimeAvg(),
            'memory_usage_avg' => $this->getMemoryUsageAvg(),

            'line_count' => $this->reflectionMethod->getEndLine() - $this->reflectionMethod->getStartLine(),
        ];
    }

    public function getStat(
        string $key,
        KataRunnerIterationMode $kataRunnerIterationMode = null
    ): mixed {
        if (! is_null($kataRunnerIterationMode)) {
            return $this->result[$kataRunnerIterationMode->value][$key];
        }

        $values = [];
        foreach (KataRunnerIterationMode::cases() as $iterationMode) {
            $values[] = $this->result[$iterationMode->value][$key];
        }

        return array_sum($values);
    }

    public function getExecutionTimeAvg(): float
    {
        $count = $this->getStat('performance_count', KataRunnerIterationMode::MAX_ITERATIONS);
        $sum = $this->getStat('execution_time_sum', KataRunnerIterationMode::MAX_ITERATIONS);

        return $sum / $count;
    }

    public function getMemoryUsageAvg(): float
    {
        $count = $this->getStat('performance_count', KataRunnerIterationMode::MAX_ITERATIONS);
        $sum = $this->getStat('memory_usage_sum', KataRunnerIterationMode::MAX_ITERATIONS);

        return $sum / $count;
    }

    public function getOutputsJson(
        KataRunnerIterationMode $kataRunnerIterationMode = KataRunnerIterationMode::MAX_ITERATIONS,
    ): mixed {
        $outputJson = $this->result[$kataRunnerIterationMode->value]['outputs_json'];

        return json_decode($outputJson);
    }

    public function getOutputsJsonFirst(): string
    {
        $outputs = $this->getOutputsJson(KataRunnerIterationMode::MAX_ITERATIONS);
        $output = array_shift($outputs);

        return is_string($output) || is_numeric($output)
            ? $output
            : json_encode($output);

        return $output[0] ?? 'N/A';
    }

    public function getOutputsJsonLast(): string
    {
        $outputs = $this->getOutputsJson(KataRunnerIterationMode::MAX_ITERATIONS);
        $output = array_pop($outputs);

        return is_string($output) || is_numeric($output)
            ? $output
            : json_encode($output);

        return $output[0] ?? 'N/A';
    }

    private function getViolations(): array
    {
        $process = new Process([
            'bin/complexity.sh',
            $this->reflectionMethod->getFileName(),
        ]);

        $process->run();

        if (
            ! $process->isSuccessful() &&
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
}
