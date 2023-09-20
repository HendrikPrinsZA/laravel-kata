<?php

namespace App\Challenges\B;

use App\Challenges\A\Eloquent as AEloquent;
use App\Models\ExchangeRate;
use App\Models\User;

class Eloquent extends AEloquent
{
    public function getCollectionAverage(int $iteration): float
    {
        return ExchangeRate::where('id', '<=', $iteration)->avg('id');
    }

    public function getCollectionUnique(int $iteration): iterable
    {
        return ExchangeRate::select('id')
            ->distinct()
            ->where('id', '<=', $iteration)
            ->pluck('id');
    }

    public function getCollectionCount(int $iteration): int
    {
        return ExchangeRate::where('id', '<=', $iteration)->count();
    }

    public function getCollectionRelatedCount(int $iteration): int
    {
        return User::where('id', '<=', $iteration)
            ->orderByDesc('id')
            ->first()
            ?->blogs()->count() ?? 0;
    }

    public function getMaxVersusOrder(int $iteration): float
    {
        return ExchangeRate::where('id', '<=', $iteration)->max('rate');
    }

    public function eagerLoading(int $iteration): float
    {
        return User::with('blogs')
            ->where('id', '<=', $iteration)
            ->get()
            ->reduce(fn (int $total, User $user) => $total + $user->blogs->count(), 0);
    }
}
