<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class BlogsSeeder extends BaseSeeder
{
    public function seed(): void
    {
        $userFirst = User::first();
        if (is_null($userFirst)) {
            Artisan::call('db:seed', ['--class' => UsersSeeder::class]);
        }

        if ($userFirst->blogs()->count() > 0) {
            return;
        }

        $maxUserBlogs = config('laravel-kata.dummy-data.max-user-blogs');
        User::all()->each(fn (User $user) => Blog::factory()->count($maxUserBlogs)->create([
            'user_id' => $user->id,
        ]));
    }
}
