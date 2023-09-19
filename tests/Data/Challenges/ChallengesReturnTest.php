<?php

it('has valid return', function (
    int $iterations,
    string $challengeA,
    string $method
) {
    $challengeB = str_replace('\\A\\', '\\B\\', $challengeA);
    $instanceA = app()->make($challengeA);
    $instanceB = app()->make($challengeB);

    $returnA = $instanceA->{$method}($iterations);
    $returnB = $instanceB->{$method}($iterations);

    expect($returnA)->not->toBeEmpty();
    expect($returnB)->not->toBeEmpty();
    expect($returnA)->toEqual($returnB);

})->with([1, 3])->with('challenge-methods');
