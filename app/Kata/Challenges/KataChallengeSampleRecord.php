<?php

namespace App\Kata\Challenges;

// use App\Kata\Challenges\KataChallengeSample;

class KataChallengeSampleRecord extends KataChallengeSample
{
    public function calculatePi(int $i): float
    {
        return round(M_PI, 2);
    }
}
