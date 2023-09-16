<?php

namespace App\Challenges\A;

use App\KataChallenge;

class Php extends KataChallenge
{
    protected const MAX_RANGE = 1000;

    /**
     * Use native functions: range()
     *
     * See https://www.thegeekstuff.com/2014/04/optimize-php-code/
     */
    public function nativeRange(int $iteration): int
    {
        $iteration = $this->getRangeLimit($iteration);

        $range = [];
        for ($i = 0; $i <= $iteration; $i++) {
            $range[] = $i;
        }

        return $this->return(count($range));
    }

    /**
     * Use native functions: array_sum
     *
     * See https://www.thegeekstuff.com/2014/04/optimize-php-code/
     */
    public function nativeSum(int $iteration): int
    {
        $numbers = range(0, $this->getRangeLimit($iteration));

        $total = 0;
        foreach ($numbers as $number) {
            $total += $number;
        }

        return $this->return($total);
    }

    protected function getRangeLimit(int $iteration): int
    {
        return $iteration <= self::MAX_RANGE ? $iteration : self::MAX_RANGE;
    }
}
