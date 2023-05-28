<?php

namespace Tests\Data\Console\Commands\FakeChallenges\A;

use App\Kata\KataChallenge;

class NotFoundA extends KataChallenge
{
    public function sample(): int
    {
        return 1;
    }
}
