<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use App\Models\User;

class KataChallengeEloquent extends KataChallenge
{
    public function baseline(): void
    {
    }

    /**
     * Eloquent collections / Average
     */
    public function getCollectionAverage(int $limit): float
    {
        return User::all()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');
    }

    /**
     * Eloquent collections / Unique
     */
    public function getCollectionUnique(int $limit): iterable
    {
        return User::all()
            ->where('id', '<=', $limit)
            ->pluck('id')
            ->unique();
    }

    /**
     * Eloquent collections / Count
     */
    public function getCollectionCount(int $limit): int
    {
        return User::all()
            ->where('id', '<=', $limit)
            ->count();
    }

    /**
     * Eloquent collections / Count
     *
     * Don’t use a collection to count the number of related entries.
     *
     * See https://codeburst.io/how-to-use-laravels-eloquent-efficiently-d46f5c392ca8
     */
    public function getCollectionRelatedCount(int $limit): int
    {
        return User::all()
            ->where('id', '<=', $limit)
            ->last()
            ->blogs
            ->count();
    }
}
