<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use App\Models\User;

class KataChallengeEloquent extends KataChallenge
{
    public function baseline(): void { }

    /**
     * Eloquent aggregates / Average
     */
    public function getModelAverage(int $limit): float
    {
        return User::where('id', '<=', $limit)
            ->get()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');
    }
}
