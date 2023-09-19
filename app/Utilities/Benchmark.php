<?php

namespace App\Utilities;

use App\Exceptions\BenchmarkProfileException;
use Closure;
use Illuminate\Support\Benchmark as SupportBenchmark;

class Benchmark extends SupportBenchmark
{
    protected const XDEBUG_TRACE_MAPPINGS = [
        'level',
        'function_number',
        'type', // 0: entry, 1: exit, 2: return
        'time',
        'memory_usage',
        'function_name',
        'function_type', // 0: internal, 1: user-defined
        'include_filename',
        'filename',
        'line_number',
        'argument_count',
        // 'argument1',
        // 'argument2',
        // 'argument3',
        // 'argument4',
        // 'argument5',
    ];

    protected static function getTraceLineValue(array $lineParts, string $key): mixed
    {
        return $lineParts[array_search($key, self::XDEBUG_TRACE_MAPPINGS)] ?? null;
    }

    public static function profileGetStats(string $filepath): array
    {
        $filepath = trim($filepath, '.xt').'.xt';

        if (! file_exists($filepath)) {
            throw new BenchmarkProfileException(sprintf(
                'Expected profile stats file not found at: %s',
                $filepath
            ));
        }

        $handle = fopen($filepath, 'r');

        $memoryUsageMax = 0;
        $start = null;
        $end = null;
        while (($line = fgets($handle)) !== false) {
            $lineParts = explode("\t", $line);

            $functionName = self::getTraceLineValue($lineParts, 'function_name');
            if (is_null($functionName)) {
                continue;
            }

            if (str_starts_with($functionName, 'App\\Challenges\\')) {
                $start = $lineParts;

                continue;
            }

            if (is_null($start)) {
                continue;
            }

            $memoryUsageLine = floatval(self::getTraceLineValue($lineParts, 'memory_usage'));
            if ($memoryUsageLine > $memoryUsageMax) {
                $memoryUsageMax = $memoryUsageLine;
            }

            $end = $lineParts;
            if ($functionName === 'xdebug_stop_trace') {
                break;
            }

        }

        if (is_null($start)) {
            throw new BenchmarkProfileException('No start found');
        }

        $timeStart = floatval(self::getTraceLineValue($start, 'time'));
        $timeEnd = floatval(self::getTraceLineValue($end, 'time'));
        $time = $timeEnd - $timeStart;

        $memoryUsageStart = floatval(self::getTraceLineValue($start, 'memory_usage'));
        $memoryUsage = $memoryUsageMax - $memoryUsageStart;

        return [
            'time' => [
                'start' => $timeStart,
                'end' => $timeEnd,
                'total' => $time,
            ],
            'memory_usage' => [
                'start' => $memoryUsageStart,
                'end' => $memoryUsageMax,
                'total' => $memoryUsage < 0 ? 0 : $memoryUsage,
            ],
        ];
    }

    public static function profile(
        Closure $benchmarkable,
        int $maxIterations = 1,
        int $maxTries = 3
    ): array {
        if (app()->runningUnitTests()) {
            throw new BenchmarkProfileException('Not allowed when testing');
        }

        if ($maxTries <= 0) {
            throw new BenchmarkProfileException('Max tries failed');
        }

        // Might want to save the static file for debugging...
        // copy($tempFilePath.'.xt', sprintf('/var/www/html/sample-memory-%s.xt', now()->format('ymd_His')));

        try {
            $tempFile = tmpfile();
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];
            xdebug_start_trace($tempFilePath, XDEBUG_TRACE_COMPUTERIZED);
            $benchmarkable();
            xdebug_stop_trace();

            return [
                ...self::profileGetStats($tempFilePath),
                'max_iterations' => $maxIterations,
            ];
        } catch (BenchmarkProfileException $_) {
            return self::profile($benchmarkable, $maxIterations, $maxTries - 1);
        } finally {
            unlink($tempFilePath.'.xt');
        }
    }
}
