<?php

namespace Tests\Data\Console\Commands\FakeChallenges\B;

use Tests\Data\Console\Commands\FakeChallenges\A\WrongOutput as AWrongOutput;

class WrongOutput extends AWrongOutput
{
    public function sample(): int
    {
        return 2;
    }
}
