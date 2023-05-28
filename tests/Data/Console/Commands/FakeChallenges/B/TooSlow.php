<?php

namespace Tests\Data\Console\Commands\FakeChallenges\B;

use Tests\Data\Console\Commands\FakeChallenges\A\TooSlow as ATooSlow;

class TooSlow extends ATooSlow
{
    public function sample(): int
    {
        sleep(1);

        return 1;
    }
}
