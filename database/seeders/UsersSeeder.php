<?php

namespace Database\Seeders;

use App\Models\User;

class UsersSeeder extends BaseSeeder
{
    public function seed(): void
    {
        if (! is_null(User::first())) {
            return;
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory(config('laravel-kata.dummy-data.max-users') - 1)->create();
    }
}
