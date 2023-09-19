<?php

namespace App\Challenges\B;

use App\Challenges\A\Php as APhp;

class Php extends APhp
{
    public function nativeRange(int $iteration): int
    {
        return count(range(0, $this->getRangeLimit($iteration)));
    }

    public function nativeSum(int $iteration): int
    {
        return array_sum(range(0, $this->getRangeLimit($iteration)));
    }
}
