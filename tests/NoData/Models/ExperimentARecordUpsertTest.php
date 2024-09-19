<?php

use App\Models\ExperimentARecord;

beforeEach(function () {
    ExperimentARecord::truncate();
});

it('can make', function () {
    $record = ExperimentARecord::factory()->make();

    expect($record)
        ->toBeInstanceOf(ExperimentARecord::class)
        ->id->toBeNull();
});

it('can create', function () {
    $record = ExperimentARecord::factory()->create();

    expect($record)
        ->toBeInstanceOf(ExperimentARecord::class)
        ->id->not->toBeNull();

    $record->delete();
});

it('can upsert', function (int $recordsCount) {
    $records = [];
    for ($iteration = 0; $iteration < $recordsCount; $iteration++) {
        $records[] = [
            'unique_field_1' => sprintf('unique_field_1-%d', $iteration),
            'unique_field_2' => sprintf('unique_field_2-%d', $iteration),
            'unique_field_3' => sprintf('unique_field_3-%d', $iteration),
            'update_field_1' => sprintf('update_field_1-%d', $iteration),
            'update_field_2' => sprintf('update_field_1-%d', $iteration),
            'update_field_3' => sprintf('update_field_1-%d', $iteration),
        ];
    }

    $upsertCount = ExperimentARecord::upsert($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    expect($upsertCount)->toBe($recordsCount);
})->with([
    'records-count-1' => 1,
    'records-count-3' => 3,
]);

it('can upsert and update', function (int $recordsCount, int $additionalRecordsCount) {
    $recordsA = [];
    $recordsB = [];
    for ($iteration = 0; $iteration < $recordsCount; $iteration++) {
        $recordsA[] = [
            'unique_field_1' => sprintf('unique_field_1-%d', $iteration),
            'unique_field_2' => sprintf('unique_field_2-%d', $iteration),
            'unique_field_3' => sprintf('unique_field_3-%d', $iteration),
            'update_field_1' => sprintf('Original %d', $iteration),
            'update_field_2' => sprintf('Original %d', $iteration),
            'update_field_3' => sprintf('Original %d', $iteration),
        ];

        $recordsB[] = [
            'unique_field_1' => sprintf('unique_field_1-%d', $iteration),
            'unique_field_2' => sprintf('unique_field_2-%d', $iteration),
            'unique_field_3' => sprintf('unique_field_3-%d', $iteration),
            'update_field_1' => sprintf('Updated %d', $iteration),
            'update_field_2' => sprintf('Updated %d', $iteration),
            'update_field_3' => sprintf('Updated %d', $iteration),
        ];
    }

    for ($iteration = $recordsCount; $iteration < ($recordsCount + $additionalRecordsCount); $iteration++) {
        $recordsB[] = [
            'unique_field_1' => sprintf('unique_field_1-%d', $iteration),
            'unique_field_2' => sprintf('unique_field_2-%d', $iteration),
            'unique_field_3' => sprintf('unique_field_3-%d', $iteration),
            'update_field_1' => sprintf('Updated %d', $iteration),
            'update_field_2' => sprintf('Updated %d', $iteration),
            'update_field_3' => sprintf('Updated %d', $iteration),
        ];
    }

    $upsertCountA = ExperimentARecord::upsert($recordsA, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    $upsertCountB = ExperimentARecord::upsert($recordsB, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    expect($upsertCountA)->toBe($recordsCount);
    expect($upsertCountB)->toBe(($recordsCount * 2) + $additionalRecordsCount, 'MySQL update should reserve the same count');
})->with([
    'records-count-1' => 1,
    'records-count-3' => 3,
])->with([
    'additional-records-count-0' => 0,
    'additional-records-count-1' => 1,
    'additional-records-count-3' => 3,
]);

it('cannot upsert and retain sequential ids', function (int $recordsCount) {
    $records = [];
    for ($iteration = 0; $iteration < $recordsCount; $iteration++) {
        $records[] = [
            'unique_field_1' => sprintf('unique_field_1-%d', $iteration),
            'unique_field_2' => sprintf('unique_field_2-%d', $iteration),
            'unique_field_3' => sprintf('unique_field_3-%d', $iteration),
            'update_field_1' => sprintf('Original %d', $iteration),
            'update_field_2' => sprintf('Original %d', $iteration),
            'update_field_3' => sprintf('Original %d', $iteration),
        ];
    }

    ExperimentARecord::upsert($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    ExperimentARecord::upsert($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    ExperimentARecord::upsert($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    $latestExperimentalRecord = ExperimentARecord::create([
        'unique_field_1' => 'unique_field_1-new',
        'unique_field_2' => 'unique_field_2-new',
        'unique_field_3' => 'unique_field_3-new',
        'update_field_1' => 'update_field_1-new',
        'update_field_2' => 'update_field_2-new',
        'update_field_3' => 'update_field_3-new',
    ]);

    $databaseIds = ExperimentARecord::query()
        ->select('id')
        ->orderBy('id')
        ->pluck('id')
        ->toArray();

    expect($latestExperimentalRecord->id)->toBe(($recordsCount * 3) + 1,
        'MySQL (ON DUPLICATE KEY) auto increment should skip on upserts, if not -> remove all bypasses in the code'
    );

    expect($databaseIds)
        ->toBe([
            ...range(1, $recordsCount),
            (($recordsCount * 3) + 1),
        ], 'MySQL (ON DUPLICATE KEY) update should not retain sequential ids -> When this changes, we can remove any bypasses in the code'
        );
})->with([
    'records-count-1' => 1,
    'records-count-3' => 3,
]);
