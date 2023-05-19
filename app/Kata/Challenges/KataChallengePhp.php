<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;

class KataChallengePhp extends KataChallenge
{
    protected const MAX_RANGE = 1000;

    public function baseline(): void
    {
    }

    /**
     * Use native functions: range()
     *
     * See https://www.thegeekstuff.com/2014/04/optimize-php-code/
     */
    public function nativeRange(int $limit): int
    {
        $limit = $this->getRangeLimit($limit);

        $range = [];
        for ($i = 0; $i <= $limit; $i++) {
            $range[] = $i;
        }

        return $this->return(count($range));
    }

    /**
     * Use native functions: array_sum
     *
     * See https://www.thegeekstuff.com/2014/04/optimize-php-code/
     */
    public function nativeSum(int $limit): int
    {
        $numbers = range(0, $this->getRangeLimit($limit));

        $total = 0;
        foreach ($numbers as $number) {
            $total += $number;
        }

        return $this->return($total);
    }

    protected function getRangeLimit(int $limit): int
    {
        return $limit <= self::MAX_RANGE ? $limit : self::MAX_RANGE;
    }
}
