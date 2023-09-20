<?php

use App\Utilities\Benchmark;

it('can read file', function (string $filename) {
    $filepath = sprintf(__DIR__.'/Files/%s', $filename);
    $stats = Benchmark::profileGetStats($filepath);

    expect($stats)->toMatchSnapshot();
})->with([
    'sleep.xt',
    'memory.xt',
]);
