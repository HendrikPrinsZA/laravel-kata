<?php

namespace App\Challenges\A;

use App\KataChallenge;
use App\Models\ExperimentARecord;

class Concurrent extends KataChallenge
{
    protected const MAX_RECORDS = 2500;

    public function sequentialVsSequential(int $iteration): int
    {
        return $this->sequential($iteration);
    }

    public function sequentialVsFork(int $iteration): int
    {
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

    // Needs work: outputs don't align when iterations start growing!
    public function sequentialVsUpsertsProcess(int $iteration): string
    {
        $this->sequentialUpserts($iteration);

        return $this->getExperimentARecordState();
    }

    // Needs work: outputs don't align when iterations start growing!
    protected function sequentialVsUpsertsFork(int $iteration): string
    {
        $this->sequentialUpserts($iteration);

        return $this->getExperimentARecordState();
    }

    public function sequentialVsUpsertsSync(int $iteration): string
    {
        $this->sequentialUpserts($iteration);

        return $this->getExperimentARecordState();
    }

    public function sequentialUpserts(int $iteration): string
    {
        ExperimentARecord::truncate();
        $recordsChunked = $this->makeRecordsChunked($iteration, 'a');

        foreach ($recordsChunked as $chunk) {
            ExperimentARecord::upsert($chunk, [
                'unique_field_1', 'unique_field_2', 'unique_field_3',
            ], [
                'position', 'update_field_1', 'update_field_2', 'update_field_3',
            ]);
        }

        return $this->getExperimentARecordState();
    }

    protected function getExperimentARecordState(): string
    {
        $response = ExperimentARecord::query()
            ->orderBy('position')
            ->pluck('position')
            ->toArray();

        return md5(implode('|', $response));
    }

    protected function makeRecordsChunked(int $iteration, string $prefix = 'source'): array
    {
        $count = $iteration * 10;
        if ($count > self::MAX_RECORDS) {
            $count = self::MAX_RECORDS;
        }
        $records = $this->makeRecords($count, $prefix);

        return array_chunk($records, ceil($count / 3));
    }

    protected function makeRecords(int $iteration, string $prefix = 'source'): array
    {
        $records = [];
        for ($position = 0; $position < $iteration; $position++) {
            $records[] = [
                'position' => $position,
                'unique_field_1' => sprintf('unique_field_1-%s-%d', $prefix, $position),
                'unique_field_2' => sprintf('unique_field_2-%s-%d', $prefix, $position),
                'unique_field_3' => sprintf('unique_field_3-%s-%d', $prefix, $position),
                'update_field_1' => sprintf('Original %d', $position),
                'update_field_2' => sprintf('Original %d', $position),
                'update_field_3' => sprintf('Original %d', $position),
            ];
        }

        return $records;
    }

    private function sequential(int $iteration): int
    {
        $sum = 0;
        for ($i = 0; $i < $iteration; $i++) {
            usleep(1000); // 1ms

            $sum += ($iteration * $i);
        }

        return $sum;
    }
}
