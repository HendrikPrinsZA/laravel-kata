<?php

namespace App\Kata\Challenges\B;

use App\Kata\Challenges\A\Php as APhp;

class Php extends APhp
{
    public function nativeRange(int $limit): int
    {
        $value = count(range(0, $this->getRangeLimit($limit)));

        return $this->return($value);
    }

    public function nativeSum(int $limit): int
    {
        $numbers = range(0, $this->getRangeLimit($limit));

        return $this->return(array_sum($numbers));
    }
}
