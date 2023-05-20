<?php

namespace App\Kata\Challenges\B;

use App\Kata\Challenges\A\Sample as ASample;

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
        for ($i = 1; $i <= $limit + 1; $i++) {
            $mod1 = $i % 3 == 0;
            $mod2 = $i % 5 == 0;

            $word = $fizzBuzz["$mod1|$mod2"] ?? $i;
            $result .= $word.'|';
        }

        return $this->return($result);
    }
}
