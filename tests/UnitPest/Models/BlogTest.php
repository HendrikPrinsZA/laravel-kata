<?php

use App\Models\Blog;
use App\Models\User;

it('can make', function () {
    $record = Blog::factory()->make();

    expect($record)
        ->toBeInstanceOf(Blog::class)
        ->id->toBeNull()
        ->user->toBeInstanceOf(User::class);
});

it('can create', function () {
    $record = Blog::factory()->create();

    expect($record)
        ->toBeInstanceOf(Blog::class)
        ->id->not->toBeNull()
        ->user->toBeInstanceOf(User::class);

    $record->delete();
});
