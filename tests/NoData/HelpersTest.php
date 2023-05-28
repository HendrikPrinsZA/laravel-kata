<?php

use App\Kata\Utilities\CodeUtility;

it('can help me code', function () {
    $reflectionClass = new ReflectionClass(CodeUtility::class);
    $reflectionMethod = $reflectionClass->getMethod('getCodeSnippet');

    $path = help_me_code($reflectionMethod);

    expect($path)
        ->toEndWith('/Kata/Utilities/CodeUtility.php:10');
});

it('can get array subset by keys', function () {
    $array = [
        'key-1' => 1,
        'key-2' => 2,
        'key-3' => 3,
        'key-4' => 4,
    ];

    $subset = array_subset_by_keys($array, ['key-2', 'key-3']);

    expect($subset)->toBe([
        'key-2' => 2,
        'key-3' => 3,
    ]);
});

it('can wrap in format', function (
    string $string,
    bool $success,
    bool $warn,
    string $expected
) {
    $string = wrap_in_format($string, $success, $warn);

    expect($string)->toBe($expected);
})->with([
    ['is true', true, false, '<fg=green>is true</>'],
    ['is false', false, false, '<fg=red>is false</>'],
    ['is true (warn)', true, true, '<fg=green>is true (warn)</>'],
    ['is false (warn)', false, true, '<fg=yellow>is false (warn)</>'],
]);

it('can convert bytes to human', function (float $bytes, string $expected) {
    $human = bytes_to_human($bytes);

    expect($human)->toBe($expected);
})->with([
    [0, '0.00000 B'],
    [0.1, '0.10000 B'],
    [1, '1.00000 B'],
    [100, '100.00000 B'],
    [1000, '0.97656 kB'],
    [10000, '9.76562 kB'],
    [100000, '97.65625 kB'],
    [1000000, '0.95367 MB'],
    [10000000, '9.53674 MB'],
    [100000000, '95.36743 MB'],
    [1000000000, '0.93132 GB'],
    [10000000000, '9.31323 GB'],
    [100000000000, '93.13226 GB'],
    [999999999999, '931.32257 GB'],
    [9999999999999, '9.09495 TB'],
]);

it('can convert time to human', function (float $bytes, string $expected) {
    $human = time_to_human($bytes);

    expect($human)->toBe($expected);
})->with([
    [0, '0.00000 ms'],
    [0.1, '0.10000 ms'],
    [1, '1.00000 ms'],
    [100, '100.00000 ms'],
    [1000, '1,000.00000 ms'],
    [10000, '10,000.00000 ms'],
    [100000, '100,000.00000 ms'],
    [1000000, '1,000,000.00000 ms'],
    [9999999, '9,999,999.00000 ms'],
    [99999999, '99,999,999.00000 ms'],
]);

it('will not redefine functions', function () {
    include 'app/helpers.php';

    expect(time_to_human(0))->toBe('0.00000 ms');
});
