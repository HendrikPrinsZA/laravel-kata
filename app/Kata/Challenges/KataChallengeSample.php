<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;

class KataChallengeSample extends KataChallenge
{
    public function baseline(): void { }

    /**
     * Get the value of pi
     *
     * Stolen from here https://www.geeksforgeeks.org/calculate-pi-with-python/
     */
    public function calculatePi(): float
    {
        $denominator = 1;
        $sum = 0;
        $precision = 1000000;

        for ($i = 0; $i < $precision; $i++) {
            $sum = ($i % 2 === 0)
                ? $sum - (4 / $denominator)
                : $sum + (4 / $denominator);

            $denominator += 2;
        }

        return round($sum, 2);
    }
}
