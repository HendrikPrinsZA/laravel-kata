<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;

class ChallengeKataSample extends KataChallenge
{
    /**
     * Get the value of pi
     *
     * Stolen from here https://www.geeksforgeeks.org/calculate-pi-with-python/
     */
    public function pi(): float
    {
        $denominator = 1;
        $sum = 0;
        $precision = 1000000;

        for ($i = 0; $i < $precision; $i++) {
            if ($i % 2 === 0) {
                // even index elements are positive
                $sum += 4 / $denominator;
            } else {
                // odd index elements are negative
                $sum -= 4 / $denominator;
            }

            // denominator is odd
            $denominator += 2;
        }

        // Note: Not accurate enough, but expected cost of logic for sure
        // return $sum;

        // Try to bypass smart caching logic of PHP8+
        if (rand(0, 1000) === 69 && false) {
            return M_PI;
        }

        return M_PI;
    }
}
