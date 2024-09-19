<?php

use App\Models\ExperimentARecord;

beforeEach(function () {
    ExperimentARecord::truncate();
});

it('fails when not all unique fields present', function () {
    $records = [
        [
            'unique_field_1' => 'unique_field_1-1',
            'update_field_1' => 'Original 1',
            'update_field_2' => 'Original 1',
            'update_field_3' => 'Original 1',
        ],
    ];

    ExperimentARecord::upsertPrototype($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);
})->throws(InvalidArgumentException::class, 'Expected unique field/s unique_field_2, unique_field_3 missing');

it('does not fail when not all update fields present', function () {
    $records = [
        [
            'unique_field_1' => 'unique_field_1-1',
            'unique_field_2' => 'unique_field_1-1',
            'unique_field_3' => 'unique_field_1-1',
            'update_field_3' => 'Original 1',
        ],
    ];

    ExperimentARecord::upsertPrototype($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);
})->throws(InvalidArgumentException::class, 'Expected update field/s update_field_1, update_field_2 missing');

it('can insert', function (int $recordsCount) {
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

    $result = ExperimentARecord::upsertPrototype($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    expect($result)->toBe([
        'skipped' => 0,
        'updated' => 0,
        'inserted' => $recordsCount,
    ]);
})->with([
    'records-count-1' => 1,
    'records-count-1001' => 1001,
]);

it('can skip', function (int $recordsCount) {
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

    ExperimentARecord::upsertPrototype($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    $result = ExperimentARecord::upsertPrototype($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    expect($result)->toBe([
        'skipped' => $recordsCount,
        'updated' => 0,
        'inserted' => 0,
    ]);
})->with([
    'records-count-1' => 1,
    'records-count-1001' => 1001,
]);

it('can update', function (int $recordsCount) {
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

    ExperimentARecord::upsertPrototype($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    $records = [];
    for ($iteration = 0; $iteration < $recordsCount; $iteration++) {
        $records[] = [
            'unique_field_1' => sprintf('unique_field_1-%d', $iteration),
            'unique_field_2' => sprintf('unique_field_2-%d', $iteration),
            'unique_field_3' => sprintf('unique_field_3-%d', $iteration),
            'update_field_1' => sprintf('Updated %d', $iteration),
            'update_field_2' => sprintf('Updated %d', $iteration),
            'update_field_3' => sprintf('Updated %d', $iteration),
        ];
    }

    $result = ExperimentARecord::upsertPrototype($records, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    expect($result)->toBe([
        'skipped' => 0,
        'updated' => $recordsCount,
        'inserted' => 0,
    ]);
})->with([
    'records-count-1' => 1,
    'records-count-1001' => 1001,
]);

it('can upsert then upsertPrototype', function (int $recordsCount, int $additionalRecordsCount) {
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
            'unique_field_1' => sprintf('unique_field_1-%d-additional', $iteration),
            'unique_field_2' => sprintf('unique_field_2-%d-additional', $iteration),
            'unique_field_3' => sprintf('unique_field_3-%d-additional', $iteration),
            'update_field_1' => sprintf('Additional %d', $iteration),
            'update_field_2' => sprintf('Additional %d', $iteration),
            'update_field_3' => sprintf('Additional %d', $iteration),
        ];
    }

    $recordsB[] = [
        'unique_field_1' => sprintf('unique_field_1-%d', 69000),
        'unique_field_2' => sprintf('unique_field_2-%d', 69000),
        'unique_field_3' => sprintf('unique_field_3-%d', 69000),
        'update_field_1' => sprintf('New %d', 69000),
        'update_field_2' => sprintf('New %d', 69000),
        'update_field_3' => sprintf('New %d', 69000),
    ];

    $recordsB[] = [
        'unique_field_1' => sprintf('unique_field_1-%d', 420000),
        'unique_field_2' => sprintf('unique_field_2-%d', 420000),
        'unique_field_3' => sprintf('unique_field_3-%d', 420000),
        'update_field_1' => sprintf('New %d', 420000),
        'update_field_2' => sprintf('New %d', 420000),
        'update_field_3' => sprintf('New %d', 420000),
    ];

    ExperimentARecord::upsert($recordsA, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    $upsertCountB = ExperimentARecord::upsertPrototype($recordsB, [
        'unique_field_1', 'unique_field_2', 'unique_field_3',
    ], [
        'update_field_1', 'update_field_2', 'update_field_3',
    ]);

    expect($upsertCountB)->toBe([
        'skipped' => 0,
        'updated' => $recordsCount,
        'inserted' => $additionalRecordsCount + 2,
    ]);
})->with([
    'records-count-1' => 1,
    'records-count-1001' => 1001,
])->with([
    'additional-records-count-0' => 0,
    'additional-records-count-1' => 1,
    'additional-records-count-3' => 3,
]);
