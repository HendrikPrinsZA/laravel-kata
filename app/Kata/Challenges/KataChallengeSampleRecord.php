<?php

namespace App\Kata\Challenges;

class KataChallengeSampleRecord extends KataChallengeSample
{
    public function calculatePi(int $i): float
    {
        return round(M_PI, 2);
    }
}
