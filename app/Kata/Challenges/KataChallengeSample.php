<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;

class KataChallengeSample extends KataChallenge
{
    public function baseline(): void
    {
    }

    /**
     * Get the value of pi
     *
     * Stolen from here https://www.geeksforgeeks.org/calculate-pi-with-python/
     */
    public function calculatePi(): float
    {
        $denominator = 1;
        $sum = 0;

        for ($i = 0; $i < 100000; $i++) {
            $sum = ($i % 2 === 0)
                ? $sum + (4 / $denominator)
                : $sum - (4 / $denominator);

            $denominator += 2;
        }

        return round($sum, 2);
    }

    public function fizzBuzz(): string
    {
        $result = '';
        for ($i = 1; $i <= 100; $i++) {
            if ($i % 3 == 0 && $i % 5 == 0) {
                $result .= 'FizzBuzz|';

                continue;
            }

            if ($i % 3 == 0) {
                $result .= 'Fizz|';

                continue;
            }

            if ($i % 5 == 0) {
                $result .= 'Buzz|';

                continue;
            }

            $result .= $i.'|';
        }

        return $result;
    }
}
