<?php

namespace App\Kata\Challenges\B;

use App\Kata\Challenges\A\Eloquent as AEloquent;
use App\Models\ExchangeRate;
use App\Models\User;

class Eloquent extends AEloquent
{
    public function getCollectionAverage(int $limit): ?float
    {
        $value = ExchangeRate::where('id', '<=', $limit + 1)->avg('id');

        return $this->return($value);
    }

    public function getCollectionUnique(int $limit): iterable
    {
        $value = ExchangeRate::select('id')
            ->distinct()
            ->where('id', '<=', $limit + 1)
            ->pluck('id');

        return $this->return($value);
    }

    public function getCollectionCount(int $limit): int
    {
        $value = ExchangeRate::where('id', '<=', $limit + 1)->count();

        return $this->return($value);
    }

    public function getCollectionRelatedCount(int $limit): int
    {
        $value = User::where('id', '<=', $limit + 1)
            ->orderByDesc('id')
            ->first()
            ?->blogs()->count() ?? 0;

        return $this->return($value);
    }

    public function getMaxVersusOrder(int $limit): float
    {
        $value = ExchangeRate::where('id', '<=', $limit + 1)->max('rate');

        return $this->return($value);
    }
}
