<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use App\Models\User;

class KataChallengeEloquent extends KataChallenge
{
    protected function setUp(): void
    {
        $this->maxIterations = 100;
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
