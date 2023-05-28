<?php

use App\Models\User;
use Database\Seeders\Models\UsersSeeder;

it('can seed', function () {
    $this->seed(UsersSeeder::class);

    expect(User::count())->toBeGreaterThan(0);
});
