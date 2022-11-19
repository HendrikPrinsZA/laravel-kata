<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends BaseSeeder
{
    public function seed(): void
    {
        if (! is_null(User::first())) {
            return;
        }

        // Ensure the id seed is reset
        DB::table('users')->truncate();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
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
