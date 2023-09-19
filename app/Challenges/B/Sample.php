<?php

namespace App\Challenges\B;

use App\Challenges\A\Sample as ASample;

class Sample extends ASample
{
    public function calculatePi(): float
    {
        return round(M_PI, 2);
    }

    public function fizzBuzz(int $iteration): string
    {
        $result = '';
        $fizzBuzz = [
            '1|1' => 'FizzBuzz',
            '|1' => 'Buzz',
            '1|' => 'Fizz',
        ];
        for ($i = 1; $i <= $iteration; $i++) {
            $mod1 = $i % 3 == 0;
            $mod2 = $i % 5 == 0;

            $word = $fizzBuzz["$mod1|$mod2"] ?? $i;
            $result .= $word.'|';
        }

        return $result;
    }

    public function memoryAllocation(int $iteration): array
    {
        $largeArray = range(1, $iteration);
        $resultArray = [];

        foreach ($largeArray as $item) {
            $resultArray[] = strrev(str_repeat($item, 100));
        }

        return $resultArray;
    }
}
