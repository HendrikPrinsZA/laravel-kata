<?php

namespace App\Challenges\A;

use App\KataChallenge;
use App\Models\ExperimentARecord;

class Concurrent extends KataChallenge
{
    private function sequential(int $iteration): int
    {
        $sum = 0;
        for ($i = 0; $i < $iteration; $i++) {
            usleep($i * $iteration);

            $sum += ($iteration * $i);
        }

        return $sum;
    }

    public function sequentialVsSequential(int $iteration): int
    {
        return $this->sequential($iteration);
    }

    public function sequentialVsFork(int $iteration): int
    {
        $iteration = $iteration > 10 ? 10 : $iteration;

        return $this->sequential($iteration);
    }

    public function sequentialVsProcess(int $iteration): int
    {
        return $this->sequential($iteration);
    }

    public function sequentialVsSync(int $iteration): int
    {
        return $this->sequential($iteration);
    }

    protected function setUp(): void
    {
        ExperimentARecord::truncate([]);
    }

    protected function sequentialVsForkUpserts(int $iteration): string
    {
        ExperimentARecord::truncate();
        $records = $this->makeRecords($iteration * 10, 'a');

        collect($records)->chunk(3)->each(function ($chunk) {
            $chunk = $chunk->toArray();
            ExperimentARecord::upsert($chunk, [
                'unique_field_1', 'unique_field_2', 'unique_field_3',
            ], [
                'update_field_1', 'update_field_2', 'update_field_3',
            ]);
        });

        $response = ExperimentARecord::query()
            ->select('update_field_3')
            ->whereLike('unique_field_1', 'a-%')
            ->orderBy('update_field_3')
            ->get()
            ->map(fn ($record) => md5($record->update_field_3))
            ->toArray();

        return md5(implode('|', $response));
    }

    protected function makeRecords(int $iteration, string $prefix = 'source'): array
    {
        $records = [];
        for ($index = 0; $index < $iteration; $index++) {
            $records[] = [
                'unique_field_1' => sprintf('%s-unique_field_1-%d-%d', $prefix, $iteration, $index),
                'unique_field_2' => sprintf('%s-unique_field_2-%d-%d', $prefix, $iteration, $index),
                'unique_field_3' => sprintf('%s-unique_field_3-%d-%d', $prefix, $iteration, $index),
                'update_field_1' => sprintf('Original %d %d', $iteration, $index),
                'update_field_2' => sprintf('Original %d %d', $iteration, $index),
                'update_field_3' => sprintf('Original %d %d', $iteration, $index),
            ];
        }

        return $records;
    }
}
