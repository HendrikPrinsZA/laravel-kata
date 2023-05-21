<?php

namespace App\Kata\Challenges\A;

use App\Kata\KataChallenge;
use App\Models\ExchangeRate;
use App\Models\User;

class Eloquent extends KataChallenge
{
    protected const MAX_INTERATIONS = 100;

    protected const EXPECTED_MODELS = [
        User::class,
        ExchangeRate::class,
    ];

    /**
     * Eloquent collections / Average
     */
    public function getCollectionAverage(int $limit): ?float
    {
        $value = ExchangeRate::all()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');

        return $this->return($value);
    }

    /**
     * Eloquent collections / Unique
     */
    public function getCollectionUnique(int $limit): iterable
    {
        $value = ExchangeRate::all()
            ->where('id', '<=', $limit)
            ->pluck('id')
            ->unique();

        return $this->return($value);
    }

    /**
     * Eloquent collections / Count
     */
    public function getCollectionCount(int $limit): int
    {
        $value = ExchangeRate::query()
            ->where('id', '<=', $limit)
            ->get()
            ->count();

        return $this->return($value);
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
        $value = User::all()
            ->where('id', '<=', $limit)
            ->last()
            ?->blogs->count() ?? 0;

        return $this->return($value);
    }

    public function getMaxVersusOrder(int $limit): float
    {
        $value = ExchangeRate::query()
            ->where('id', '<=', $limit)
            ->orderByDesc('rate')
            ->first()?->rate;

        return $this->return($value);
    }
}
