<?php

namespace App\Kata\Challenges;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class EloquentKata
{
    /**
     * Optimise aggregates functionality
     *
     * Refs
     * - https://www.keycdn.com/blog/php-performance#5-use-isset
     */
    public function aggregates(int $limit): float
    {
        DB::raw('FLUSH TABLES;');
        return User::where('id', '<=', $limit)
            ->get()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');

        return User::all()
            ->where('id', '<=', $limit)
            ->sortBy('id')
            ->average('id');
    }
}
