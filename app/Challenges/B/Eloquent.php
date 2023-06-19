<?php

namespace App\Challenges\B;

use App\Challenges\A\Eloquent as AEloquent;
use App\Models\ExchangeRate;
use App\Models\User;

class Eloquent extends AEloquent
{
    public function getCollectionAverage(int $limit): float
    {
        $value = ExchangeRate::where('id', '<=', $limit)->avg('id');

        return $this->return($value);
    }

    public function getCollectionUnique(int $limit): iterable
    {
        $value = ExchangeRate::select('id')
            ->distinct()
            ->where('id', '<=', $limit)
            ->pluck('id');

        return $this->return($value);
    }

    public function getCollectionCount(int $limit): int
    {
        $value = ExchangeRate::where('id', '<=', $limit)->count();

        return $this->return($value);
    }

    public function getCollectionRelatedCount(int $limit): int
    {
        $value = User::where('id', '<=', $limit)
            ->orderByDesc('id')
            ->first()
            ?->blogs()->count() ?? 0;

        return $this->return($value);
    }

    public function getMaxVersusOrder(int $limit): float
    {
        $value = ExchangeRate::where('id', '<=', $limit)->max('rate');

        return $this->return($value);
    }

    public function eagerLoading(int $limit): float
    {
        $value = User::with('blogs')->where('id', '<=', $limit)->get()
            ->reduce(
                fn (int $total, User $user) => $total + $user->blogs->count(), 0);

        return $this->return($value);
    }
}