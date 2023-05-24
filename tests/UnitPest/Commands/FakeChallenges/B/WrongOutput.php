<?php

namespace Tests\UnitPest\Commands\FakeChallenges\B;

use Tests\UnitPest\Commands\FakeChallenges\A\WrongOutput as AWrongOutput;

class WrongOutput extends AWrongOutput
{
    public function sample(): int
    {
        return 2;
    }
}
