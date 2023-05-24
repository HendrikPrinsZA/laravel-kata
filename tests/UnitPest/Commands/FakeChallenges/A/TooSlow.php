<?php

namespace Tests\UnitPest\Commands\FakeChallenges\A;

use App\Kata\KataChallenge;

class TooSlow extends KataChallenge
{
    public function sample(): int
    {
        return 1;
    }
}
