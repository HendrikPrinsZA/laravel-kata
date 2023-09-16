<?php

namespace Tests\Data\Console\Commands\FakeChallenges\A;

use App\KataChallenge;
use App\Models\Blog;

class NotFound extends KataChallenge
{
    protected const EXPECTED_MODELS = [
        Blog::class,
    ];

    public function sample(): int
    {
        return $this->return(1);
    }
}
