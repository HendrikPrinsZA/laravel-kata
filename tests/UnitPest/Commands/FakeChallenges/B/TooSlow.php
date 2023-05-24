<?php

namespace Tests\UnitPest\Commands\FakeChallenges\B;

use Tests\UnitPest\Commands\FakeChallenges\A\TooSlow as ATooSlow;

class TooSlow extends ATooSlow
{
    public function sample(): int
    {
        sleep(1);

        return 1;
    }
}
