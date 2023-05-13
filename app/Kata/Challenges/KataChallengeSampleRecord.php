<?php

namespace App\Kata\Challenges;

class KataChallengeSampleRecord extends KataChallengeSample
{
    public function calculatePi(): float
    {
        return round(M_PI, 2);
    }

    public function fizzBuzz(): string
    {
        $result = '';
        for ($i = 1; $i <= 100; $i++) {
            if ($i % 3 == 0) {
                $result .= $i % 5 === 0 ? 'FizzBuzz|' : 'Fizz|';

                continue;
            }
            $result .= $i % 5 === 0 ? 'Buzz|' : $i.'|';
        }

        return $result;
    }
}
