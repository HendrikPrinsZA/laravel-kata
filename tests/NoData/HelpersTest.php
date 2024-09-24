<?php

use App\Utilities\CodeUtility;

it('can help me code', function () {
    $reflectionClass = new ReflectionClass(CodeUtility::class);
    $reflectionMethod = $reflectionClass->getMethod('getCodeSnippet');

    $path = help_me_code($reflectionMethod);

    expect($path)
        ->toEndWith('/Utilities/CodeUtility.php:10');
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

it('can convert bytes to human', function (float $bytes) {
    $human = bytes_to_human($bytes);

    expect($human)->toMatchSnapshot();
})->with([
    0,
    0.1,
    1,
    2,
    9999999999999,
]);

it('can convert time to human (digital)', function (float $bytes) {
    $human = time_to_human($bytes, digital: true);

    expect($human)->toMatchSnapshot();
})->with([
    0,
    0.1,
    1,
    2,
    9999999999999,
]);

it('can convert time to human (false)', function (float $bytes) {
    $human = time_to_human($bytes, digital: false);

    expect($human)->toMatchSnapshot();
})->with([
    0,
    0.1,
    1,
    2,
    99999999,
]);

it('will not redefine functions', function () {
    include 'app/helpers.php';

    expect(time_to_human(0))->toBe('0.000000000 s');
});
