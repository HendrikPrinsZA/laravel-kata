<?php

namespace App\Kata\Challenges;

use App\Models\User;

class KataChallengeEloquentRecord extends KataChallengeEloquent
{
    public function getModelAverage(int $limit): float
    {
        return User::where('id', '<=', $limit)->avg('id');
    }

    public function getModelUnique(int $limit): float
    {
        $ids = User::query()
            ->select('id')
            ->distinct()
            ->where('id', '<=', $limit)
            ->pluck('id');

        return $ids->average();
    }
}
