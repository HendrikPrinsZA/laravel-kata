<?php

namespace App\Challenges\A;

use App\KataChallenge;
use App\Models\ExperimentARecord;
use Fiber;

class ExperimentA extends KataChallenge
{
    protected const MAX_INTERATIONS = 50;

    public function inserts(int $iteration): string
    {
        $records = $this->truncateAndMakeFreshRecords($iteration);

        ExperimentARecord::upsertPrototype($records, [
            'unique_field_1', 'unique_field_2', 'unique_field_3',
        ], [
            'update_field_1', 'update_field_2', 'update_field_3',
        ]);

        $response = ExperimentARecord::query()
            ->select('update_field_3')
            ->get()
            ->map(fn ($record) => md5($record->update_field_3))
            ->toArray();

        return md5(implode('|', $response));
    }

    public function updates(int $iteration): string
    {
        $records = $this->truncateAndMakeFreshRecords($iteration);
        $this->insertChunked($records);

        $records = collect($records)->map(fn ($record) => array_merge($record, [
            'update_field_1' => 'Updated 1',
            'update_field_2' => 'Updated 2',
            'update_field_3' => 'Updated 3',
        ]))->toArray();
        ExperimentARecord::upsertPrototype($records, [
            'unique_field_1', 'unique_field_2', 'unique_field_3',
        ], [
            'update_field_1', 'update_field_2', 'update_field_3',
        ]);

        $response = ExperimentARecord::query()
            ->select('update_field_3')
            ->get()
            ->map(fn ($record) => md5($record->update_field_3))
            ->toArray();

        return md5(implode('|', $response));
    }

    public function partialUpdates(int $iteration): string
    {
        $records = $this->truncateAndMakeFreshRecords($iteration);

        $partialRecords = array_slice($records, 0, ceil($iteration / 2));
        $this->insertChunked($partialRecords);

        ExperimentARecord::upsertPrototype($records, [
            'unique_field_1', 'unique_field_2', 'unique_field_3',
        ], [
            'update_field_1', 'update_field_2', 'update_field_3',
        ]);

        $response = ExperimentARecord::query()
            ->select('update_field_3')
            ->get()
            ->map(fn ($record) => md5($record->update_field_3))
            ->toArray();

        return md5(implode('|', $response));
    }

    public function parallelPartialUpdates(int $iteration): string
    {
        $records = $this->truncateAndMakeFreshRecords($iteration);

        $partialRecords = array_slice($records, 0, ceil($iteration / 2));
        $this->insertChunked($partialRecords);

        $chunks = array_chunk($records, ceil(count($records) / 3));
        $fiberList = [];
        foreach ($chunks as $chunk) {
            $fiber = new Fiber(function () use ($chunk) {
                return ExperimentARecord::upsertPrototype($chunk, [
                    'unique_field_1', 'unique_field_2', 'unique_field_3',
                ], [
                    'update_field_1', 'update_field_2', 'update_field_3',
                ]);
            });

            $fiber->start();
            $fiberList[] = $fiber;
        }

        while ($fiberList) {
            foreach ($fiberList as $idx => $fiber) {
                if ($fiber->isTerminated()) {
                    $fiber->getReturn();

                    unset($fiberList[$idx]);

                    continue;
                }

                $fiber->resume();
            }
        }

        $response = ExperimentARecord::query()
            ->select('update_field_3')
            ->get()
            ->map(fn ($record) => md5($record->update_field_3))
            ->toArray();

        return md5(implode('|', $response));
    }

    protected function truncateAndMakeFreshRecords(int $iteration): array
    {
        $this->truncate();

        $records = [];
        for ($index = 0; $index < $iteration * 100; $index++) {
            $records[] = [
                'unique_field_1' => sprintf('unique_field_1-%d', $index),
                'unique_field_2' => sprintf('unique_field_2-%d', $index),
                'unique_field_3' => sprintf('unique_field_3-%d', $index),
                'update_field_1' => sprintf('Original %d', $index),
                'update_field_2' => sprintf('Original %d', $index),
                'update_field_3' => sprintf('Original %d', $index),
            ];
        }

        return $records;
    }

    protected function truncate(): void
    {
        ExperimentARecord::truncate();
    }

    protected function insertChunked(array $records): void
    {
        $chunkSize = 1000;
        $chunks = array_chunk($records, $chunkSize);

        foreach ($chunks as $chunk) {
            ExperimentARecord::insert($chunk);

            usleep(3000); // 3ms
        }
    }

    protected function upsertChunked(array $records, array $uniqueFields, array $updateFields): void
    {
        $chunkSize = 1000;
        $chunks = array_chunk($records, $chunkSize);

        foreach ($chunks as $chunk) {
            ExperimentARecord::upsert($chunk, $uniqueFields, $updateFields);

            usleep(3000); // 3ms
        }
    }
}
