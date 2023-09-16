<?php

namespace App\Challenges\B;

use App\Challenges\A\Eloquent as AEloquent;
use App\Models\ExchangeRate;
use App\Models\User;

class Eloquent extends AEloquent
{
    public function getCollectionAverage(int $iteration): float
    {
        $value = ExchangeRate::where('id', '<=', $iteration)->avg('id');

        return $this->return($value);
    }

    public function getCollectionUnique(int $iteration): iterable
    {
        $value = ExchangeRate::select('id')
            ->distinct()
            ->where('id', '<=', $iteration)
            ->pluck('id');

        return $this->return($value);
    }

    public function getCollectionCount(int $iteration): int
    {
        $value = ExchangeRate::where('id', '<=', $iteration)->count();

        return $this->return($value);
    }

    public function getCollectionRelatedCount(int $iteration): int
    {
        $value = User::where('id', '<=', $iteration)
            ->orderByDesc('id')
            ->first()
            ?->blogs()->count() ?? 0;

        return $this->return($value);
    }

    public function getMaxVersusOrder(int $iteration): float
    {
        $value = ExchangeRate::where('id', '<=', $iteration)->max('rate');

        return $this->return($value);
    }

    public function eagerLoading(int $iteration): float
    {
        $value = User::with('blogs')->where('id', '<=', $iteration)->get()
            ->reduce(
                fn (int $total, User $user) => $total + $user->blogs->count(), 0);

        return $this->return($value);
    }
}
