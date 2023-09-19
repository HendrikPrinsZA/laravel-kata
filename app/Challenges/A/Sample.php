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

        return round($sum, 2);
    }

    public function fizzBuzz(int $iteration): string
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
        for ($i = 1; $i <= $iteration; $i++) {
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

        return $result;
    }

    public function memoryAllocation(int $iteration): string
    {
        $largeArray = range(1, $iteration);
        $tempArray = [];

        foreach ($largeArray as $item) {
            $tempArray[] = str_repeat($item, floor($iteration / 10) + 1);
        }

        $resultArray = [];
        foreach ($tempArray as $item) {
            $resultArray[] = strrev($item);
        }

        return md5(implode('|', $resultArray));
    }
}
