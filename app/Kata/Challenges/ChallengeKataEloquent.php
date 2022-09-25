<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChallengeKataEloquent extends KataChallenge
{
    /**
     * Eloquent aggregates / Average
     */
    public function get_model_average(int $limit): float
    {
        return User::where('id', '<=', $limit)
            ->get()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');
    }
}
