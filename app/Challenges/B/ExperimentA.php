<?php

namespace App\Challenges\B;

use App\Challenges\A\ExperimentA as AExperimentA;
use App\Models\ExperimentARecord;
use Fiber;

class ExperimentA extends AExperimentA
{
    public function inserts(int $iteration): string
    {
        $records = $this->truncateAndMakeFreshRecords($iteration);
        $this->upsertChunked($records, [
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
        $this->upsertChunked($records, [
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

        $this->upsertChunked($records, [
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
                return $this->upsertChunked($chunk, [
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
}
