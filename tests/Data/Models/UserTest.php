<?php

use App\Models\User;

it('can make', function () {
    $record = User::factory()->make();

    expect($record)
        ->toBeInstanceOf(User::class)
        ->id->toBeNull();
});

it('can create', function () {
    $record = User::factory()->create();

    expect($record)
        ->toBeInstanceOf(User::class)
        ->id->not->toBeNull();

    $record->delete();
});
