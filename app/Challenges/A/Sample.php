<?php

namespace App\Challenges\A;

use App\KataChallenge;

class Sample extends KataChallenge
{
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

        return $this->return(round($sum, 2));
    }

    public function fizzBuzz(int $limit): string
    {
        $isDivisible = function ($number, $divisor) {
            for ($i = 1; $i <= $number; $i++) {
                if ($i * $divisor == $number) {
                    return true;
                }
            }

            return false;
        };

        $result = '';
        for ($i = 1; $i <= $limit; $i++) {
            $output = '';

            if ($isDivisible($i, 3)) {
                $output .= 'Fizz';
            }

            if ($isDivisible($i, 5)) {
                $output .= 'Buzz';
            }

            if ($output == '') {
                $output = $i;
            }

            $result .= $output.'|';
        }

        return $this->return($result);
    }
}
