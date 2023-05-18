<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use App\Models\ExchangeRate;
use App\Models\User;

class KataChallengeEloquent extends KataChallenge
{
    protected const MAX_INTERATIONS = 100;

    protected const EXPECTED_MODELS = [
        User::class,
        ExchangeRate::class,
    ];

    public function baseline(): void
    {
    }

    /**
     * Eloquent collections / Average
     */
    public function getCollectionAverage(int $limit): ?float
    {
        return ExchangeRate::all()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');
    }

    /**
     * Eloquent collections / Unique
     */
    public function getCollectionUnique(int $limit): iterable
    {
        return ExchangeRate::all()
            ->where('id', '<=', $limit)
            ->pluck('id')
            ->unique();
    }

    /**
     * Eloquent collections / Count
     */
    public function getCollectionCount(int $limit): int
    {
        return ExchangeRate::all()
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
            ?->blogs->count() ?? 0;
    }

    public function getMaxVersusOrder(int $limit): float
    {
        $minId = ExchangeRate::min('id');

        return ExchangeRate::query()
            ->where('id', '<=', $minId + 1)
            ->orderByDesc('rate')
            ->first()?->rate;
    }
}
