<?php

it('has valid return', function (string $challengeA, string $method) {
    $challengeB = str_replace('\\A\\', '\\B\\', $challengeA);
    $instanceA = app()->make($challengeA);
    $instanceB = app()->make($challengeB);

    $returnA = $instanceA->{$method}(3);
    $returnB = $instanceB->{$method}(3);
    expect($returnA)->toEqual($returnB);
})->with('challenge-methods');
