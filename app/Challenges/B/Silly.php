<?php

namespace App\Challenges\B;

use App\Challenges\A\Silly as ASilly;

class Silly extends ASilly
{
    public function isEven(int $iteration): bool
    {
        $iteration = ($iteration > self::PHP_MEM_MAX_ITERATIONS)
            ? self::PHP_MEM_MAX_ITERATIONS
            : $iteration;

        return $iteration % 2 == 0;
    }
}
