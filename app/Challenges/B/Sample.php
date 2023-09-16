<?php

namespace App\Challenges\B;

use App\Challenges\A\Sample as ASample;

class Sample extends ASample
{
    public function calculatePi(): float
    {
        return $this->return(round(M_PI, 2));
    }

    public function fizzBuzz(int $limit): string
    {
        $result = '';
        $fizzBuzz = [
            '1|1' => 'FizzBuzz',
            '|1' => 'Buzz',
            '1|' => 'Fizz',
        ];
        for ($i = 1; $i <= $limit; $i++) {
            $mod1 = $i % 3 == 0;
            $mod2 = $i % 5 == 0;

            $word = $fizzBuzz["$mod1|$mod2"] ?? $i;
            $result .= $word.'|';
        }

        return $this->return($result);
    }

    public function memoryAllocation(int $limit): array
    {
        $largeArray = range(1, $limit);
        $resultArray = [];

        foreach ($largeArray as $item) {
            $resultArray[] = strrev(str_repeat($item, 100));
        }

        return $this->return($resultArray);
    }
}
