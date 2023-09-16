<?php

namespace App\Challenges\A;

use App\KataChallenge;
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
    public function getCollectionAverage(int $iteration): float
    {
        $value = ExchangeRate::where('id', '<=', $iteration)
            ->get()
            ->average('id');

        return $this->return($value);
    }

    /**
     * Eloquent collections / Unique
     */
    public function getCollectionUnique(int $iteration): iterable
    {
        $value = ExchangeRate::all()
            ->where('id', '<=', $iteration)
            ->pluck('id')
            ->unique();

        return $this->return($value);
    }

    /**
     * Eloquent collections / Count
     */
    public function getCollectionCount(int $iteration): int
    {
        $value = ExchangeRate::query()
            ->where('id', '<=', $iteration)
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
    public function getCollectionRelatedCount(int $iteration): int
    {
        $value = User::all()
            ->where('id', '<=', $iteration)
            ->last()
            ?->blogs->count() ?? 0;

        return $this->return($value);
    }

    public function getMaxVersusOrder(int $iteration): float
    {
        $value = ExchangeRate::query()
            ->where('id', '<=', $iteration)
            ->orderByDesc('rate')
            ->first()?->rate;

        return $this->return($value);
    }

    public function eagerLoading(int $iteration): float
    {
        $value = 0;

        /** @var User $user */
        foreach (User::where('id', '<=', $iteration)->get() as $user) {
            $value += $user->blogs()->count();
        }

        return $this->return($value);
    }
}
