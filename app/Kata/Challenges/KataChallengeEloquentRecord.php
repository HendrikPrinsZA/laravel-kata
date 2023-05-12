<?php

namespace App\Kata\Challenges;

use App\Models\User;

class KataChallengeEloquentRecord extends KataChallengeEloquent
{
    public function getCollectionAverage(int $limit): ?float
    {
        return User::where('id', '<=', $limit)->avg('id');
    }

    public function getCollectionUnique(int $limit): iterable
    {
        return User::query()
            ->select('id')
            ->distinct()
            ->where('id', '<=', $limit)
            ->pluck('id');
    }

    public function getCollectionCount(int $limit): int
    {
        return User::where('id', '<=', $limit)->count();
    }

    public function getCollectionRelatedCount(int $limit): int
    {
        return User::where('id', '<=', $limit)
            ->orderByDesc('id')
            ->first()
            ?->blogs()->count() ?? 0;
    }
}
