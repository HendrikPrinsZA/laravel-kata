<?php

namespace App\Challenges\A;

use App\KataChallenge;

class Sample extends KataChallenge
{
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

    public function memoryAllocation(int $limit): array
    {
        $largeArray = range(1, $limit);
        $tempArray = [];

        foreach ($largeArray as $item) {
            $tempArray[] = str_repeat($item, 100);
        }

        $resultArray = [];
        foreach ($tempArray as $item) {
            $resultArray[] = strrev($item);
        }

        return $this->return($resultArray);
    }
}
