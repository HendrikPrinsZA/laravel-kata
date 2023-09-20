<?php

namespace App\Challenges\A;

use App\KataChallenge;
use App\Models\ExchangeRate;
use App\Models\User;

class Eloquent extends KataChallenge
{
    protected const MAX_INTERATIONS = 50;

    protected const EXPECTED_MODELS = [
        User::class,
        ExchangeRate::class,
    ];

    public function getCollectionAverage(int $iteration): float
    {
        return ExchangeRate::where('id', '<=', $iteration)
            ->get()
            ->average('id');
    }

    public function getCollectionUnique(int $iteration): iterable
    {
        return ExchangeRate::query()
            ->where('id', '<=', $iteration)
            ->get()
            ->pluck('id')
            ->unique();
    }

    public function getCollectionCount(int $iteration): int
    {
        return ExchangeRate::query()
            ->where('id', '<=', $iteration)
            ->get()
            ->count();
    }

    public function getCollectionRelatedCount(int $iteration): int
    {
        return User::all()
            ->where('id', '<=', $iteration)
            ->last()
            ?->blogs->count() ?? 0;
    }

    public function getMaxVersusOrder(int $iteration): float
    {
        return ExchangeRate::query()
            ->where('id', '<=', $iteration)
            ->orderByDesc('rate')
            ->first()?->rate;
    }

    public function eagerLoading(int $iteration): float
    {
        $value = 0;

        /** @var User $user */
        foreach (User::where('id', '<=', $iteration)->get() as $user) {
            $value += $user->blogs()->count();
        }

        return $value;
    }
}
