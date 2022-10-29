<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use App\Models\User;
use Database\Seeders\UsersSeeder;
use Illuminate\Support\Facades\Artisan;

class KataChallengeEloquent extends KataChallenge
{
    protected function setUp(): void
    {
        if (User::first()?->id > 0) {
        } else {
            Artisan::call('db:seed', [
                '--class' => UsersSeeder::class,
                '--force' => true,
            ]);
        }
    }

    public function baseline(): void
    {
    }

    /**
     * Eloquent aggregates / Average
     */
    public function getModelAverage(int $limit): float
    {
        return User::all()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');
    }

    public function getModelUnique(int $limit): float
    {
        $ids = User::where('id', '<=', $limit)
            ->pluck('id')
            ->unique();

        return $ids->average();
    }
}
