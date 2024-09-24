<?php

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;

it('can get unified diff', function (mixed $a, mixed $b, string $expected) {
    $differ = new Differ(new DiffOnlyOutputBuilder(header: "--- A\n+++ B\n"));
    $diff = $differ->diff($a, $b);

    expect($diff)->toBe($expected);
})->with([
    'booleans' => [
        true,
        false,
        <<<'EXPECTED'
--- A
+++ B
-1

EXPECTED,
    ],
    'strings' => [
        'line 1',
        'line 2',
        <<<'EXPECTED'
--- A
+++ B
-line 1
+line 2

EXPECTED,
    ],
    'arrays-a' => [
        [
            'line 1',
            'line 2',
        ],
        [
            'line 2',
        ],
        <<<'EXPECTED'
--- A
+++ B
-line 1

EXPECTED,
    ],
    'arrays-b' => [
        [
            'line 1',
        ],
        [
            'line 2',
            'line 3',
        ],
        <<<'EXPECTED'
--- A
+++ B
-line 1
+line 2
+line 3

EXPECTED,
    ],
]);
