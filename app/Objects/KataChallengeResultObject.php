<?php

namespace App\Objects;

use App\Enums\KataRunnerIterationMode;
use App\Utilities\CodeUtility;
use Illuminate\Http\Resources\Json\JsonResource;
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

    public function getStats(): array
    {
        $extraReturn = [];

        $violations = $this->getViolations();

        $profile = data_get($this->result, KataRunnerIterationMode::XDEBUG_PROFILE->value, []);
        if (! empty($profile)) {
            $maxIterations = data_get($profile, 'max_iterations', 1);
            $extraReturn['profile_memory_usage_avg'] = data_get($profile, 'memory_usage.total', 0) / $maxIterations;
            $extraReturn['profile_time_avg'] = data_get($profile, 'time.total', 0) / $maxIterations;
        }

        return [
            ...$extraReturn,
            '_result' => $this->result, // Why normalize?

            'violations' => $violations,
            'violations_count' => count($violations),

            'iteration_count' => $this->getStat('iteration_count', KataRunnerIterationMode::MAX_SECONDS),
            'outputs_md5' => $this->getStat('outputs_md5', KataRunnerIterationMode::MAX_ITERATIONS),

            'execution_time_avg' => $this->getStat('execution_time_avg', KataRunnerIterationMode::MAX_ITERATIONS),
            'execution_time_sum' => $this->getStat('execution_time_sum', KataRunnerIterationMode::MAX_ITERATIONS),

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
        // Skip when not running from the cli
        if (! app()->runningInConsole()) {
            return [];
        }

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
                // Ignore: Avoid using static access to class '\App\Models\ExchangeRate' in method 'getMaxVersusOrder'
                // - Standard Laravel functionality
                if (str_starts_with($violation['description'], 'Avoid using static access to class')) {
                    continue;
                }

                $violations->push($violation);
            }
        }

        return $violations->toArray();
    }
}
