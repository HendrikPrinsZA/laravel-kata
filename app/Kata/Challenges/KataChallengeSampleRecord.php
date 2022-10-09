<?php

namespace App\Kata\Challenges;

class KataChallengeSampleRecord extends KataChallengeSample
{
    /**
     * Get the value of pi
     */
    public function pi(): float
    {
        return round(pi(), 2);
    }
}
