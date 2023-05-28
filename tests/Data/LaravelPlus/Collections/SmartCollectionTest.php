<?php

use App\Collections\UserCollection;
use App\Models\User;

function scopeSmartCollectionTestMakeUsers(int $count): UserCollection
{
    /** @var App\Collections\UserCollection $users */
    $users = User::factory($count)->make();
    $users->map(function (User $user, int $index) {
        $user->email = sprintf('user-%d@test.com', $index);

        return $user;
    });

    return $users;
}

it('can upsert new records', function (int $count) {
    $users = scopeSmartCollectionTestMakeUsers($count);
    $success = $users->upsert();
    expect($success)->toBeTrue();

    $userCount = User::where('email', 'LIKE', 'user-%@test.com')->count();
    expect($userCount)->toBe($count);

    User::where('email', 'LIKE', 'user-%@test.com')->delete();
})->with([
    1, 100, 1000,
]);

it('can upsert existing records', function (int $count) {
    $users = scopeSmartCollectionTestMakeUsers($count);
    $success = $users->upsert();
    expect($success)->toBeTrue();

    $userCount = User::where('email', 'LIKE', 'user-%@test.com')->count();
    expect($userCount)->toBe($count);

    /** @var App\Collections\UserCollection $newUsers */
    $newUsers = User::where('email', 'LIKE', 'user-%@test.com')->get();

    // Update email address for all new
    $newUsers->map(function (User $user, int $index) {
        $user->email = sprintf('user-new-%d@test.com', $index);

        return $user;
    });

    $newUsers->upsert();

    // Only new users
    $userCount = User::where('email', 'LIKE', 'user-new-%@test.com')->count();
    expect($userCount)->toBe($userCount);

    User::where('email', 'LIKE', 'user-new-%@test.com')->delete();
})->with([
    1, 100, 1000,
]);

it('can delete records', function (int $count) {
    $users = scopeSmartCollectionTestMakeUsers($count);
    $success = $users->upsert();
    expect($success)->toBeTrue();

    $users = User::where('email', 'LIKE', 'user-%@test.com')->get();
    $users->delete();

    $userCount = User::where('email', 'LIKE', 'user-%@test.com')->count();
    expect($userCount)->toBe(0);
})->with([
    1, 100, 1000,
]);
