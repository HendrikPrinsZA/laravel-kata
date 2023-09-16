<?php

namespace App\Challenges\B;

use App\Challenges\A\Php as APhp;

class Php extends APhp
{
    public function nativeRange(int $iteration): int
    {
        $value = count(range(0, $this->getRangeLimit($iteration)));

        return $this->return($value);
    }

    public function nativeSum(int $iteration): int
    {
        $numbers = range(0, $this->getRangeLimit($iteration));

        return $this->return(array_sum($numbers));
    }
}
