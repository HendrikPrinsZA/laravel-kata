<?php

namespace Database\Seeders\Models;

use App\Models\User;

class UsersSeeder extends ModelSeeder
{
    public function seed(): void
    {
        $maxUsers = config('laravel-kata.dummy-data.max-users');
        if (User::count() >= $maxUsers) {
            return;
        }

        if (! User::firstWhere('email', 'test@example.com')) {
            User::factory()->makeOne([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])->save();
        }

        /** @var \App\Collections\UserCollection $users */
        $users = User::factory(config('laravel-kata.dummy-data.max-users') - 1)
            ->make();

        $users->upsert();
    }
}
