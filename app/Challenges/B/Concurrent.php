<?php

namespace App\Challenges\B;

use App\Challenges\A\Concurrent as AConcurrent;
use App\Models\ExperimentARecord;
use Illuminate\Support\Facades\Concurrency;

class Concurrent extends AConcurrent
{
    private function makeFunctions(int $iteration): array
    {
        $fns = [];
        for ($i = 0; $i < $iteration; $i++) {
            $fns[] = function () use ($iteration, $i) {
                // usleep(10000); // 10ms
                usleep($i * $iteration);

                return $iteration * $i;
            };
        }

        return $fns;
    }

    public function sequentialVsFork(int $iteration): int
    {
        $iteration = $iteration > 10 ? 10 : $iteration;
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

    public function sequentialVsForkUpserts(int $iteration): string
    {
        ExperimentARecord::truncate();
        $records = $this->makeRecords($iteration * 10, 'b');

        $functions = [];
        collect($records)->chunk(3)->each(function ($chunk) use (&$functions) {

            $functions[] = function () use ($chunk) {
                ExperimentARecord::upsertPrototype($chunk->toArray(), [
                    'unique_field_1', 'unique_field_2', 'unique_field_3',
                ], [
                    'update_field_1', 'update_field_2', 'update_field_3',
                ]);

                return true;
            };
        });

        // dd($functions);

        Concurrency::driver('process')->run($functions);
        // Concurrency::driver('fork')->run($functions);
        // Concurrency::driver('sync')->run($functions);

        $response = ExperimentARecord::query()
            ->select('update_field_3')
            ->whereLike('unique_field_1', 'b-%')
            ->orderBy('update_field_3')
            ->get()
            ->map(fn ($record) => md5($record->update_field_3))
            ->toArray();

        return md5(implode('|', $response));
    }
}
