<?php

namespace Tests\Data\Console\Commands\FakeChallenges\A;

use App\KataChallenge;

class WrongOutput extends KataChallenge
{
    public function sample(): int
    {
        return 1;
    }
}
