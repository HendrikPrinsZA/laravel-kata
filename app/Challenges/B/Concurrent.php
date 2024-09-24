<?php

namespace App\Challenges\B;

use App\Challenges\A\Concurrent as AConcurrent;
use App\Models\ExperimentARecord;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Illuminate\Support\Facades\Concurrency;

class Concurrent extends AConcurrent
{
    public function sequentialVsFork(int $iteration): int
    {
        $results = Concurrency::driver('fork')->run($this->makeFunctions($iteration));

        return array_sum($results);
    }

    public function sequentialVsProcess(int $iteration): int
    {
        $results = Concurrency::driver('process')->run($this->makeFunctions($iteration));

        return array_sum($results);
    }

    public function sequentialVsSync(int $iteration): int
    {
        $results = Concurrency::driver('sync')->run($this->makeFunctions($iteration));

        return array_sum($results);
    }

    public function sequentialVsUpsertsProcess(int $iteration): string
    {
        return $this->sequentialVsForkUpsertsByDriver($iteration, 'process');
    }

    public function sequentialVsUpsertsFork(int $iteration): string
    {
        return $this->sequentialVsForkUpsertsByDriver($iteration, 'fork');
    }

    public function sequentialVsUpsertsSync(int $iteration): string
    {
        return $this->sequentialVsForkUpsertsByDriver($iteration, 'sync');
    }

    private function sequentialVsForkUpsertsByDriver(int $iteration, string $driver): string
    {
        if (! in_array($driver, ['sync', 'fork', 'process'])) {
            throw new InvalidArgument(sprintf('Invalid driver: %s', $driver));
        }

        ExperimentARecord::truncate();
        $recordsChunked = $this->makeRecordsChunked($iteration, 'b');

        $functions = [];
        foreach ($recordsChunked as $index => $chunk) {
            $functions[] = function () use ($chunk) {
                ExperimentARecord::upsertPrototype($chunk, [
                    'unique_field_1', 'unique_field_2', 'unique_field_3',
                ], [
                    'position', 'update_field_1', 'update_field_2', 'update_field_3',
                ]);
            };
        }

        Concurrency::driver($driver)->run($functions);

        return $this->getExperimentARecordState();
    }

    private function makeFunctions(int $iteration): array
    {
        $fns = [];
        for ($i = 0; $i < $iteration; $i++) {
            $fns[] = function () use ($iteration, $i) {
                usleep(1000); // 1ms

                return $iteration * $i;
            };
        }

        return $fns;
    }
}
