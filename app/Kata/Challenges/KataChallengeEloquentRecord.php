<?php

namespace App\Kata\Challenges;

use App\Models\User;

class KataChallengeEloquentRecord extends KataChallengeEloquent
{
    public function getModelAverage(int $limit): float
    {
        return User::where('id', '<=', $limit)->avg('id');
    }
}
