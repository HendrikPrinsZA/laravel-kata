<?php

use App\Kata\Challenges\KataChallengeEloquent;
use App\Kata\Challenges\KataChallengePhp;
use App\Kata\Challenges\KataChallengeSample;

return [
    'challenges' => [
        KataChallengeSample::class,
        KataChallengePhp::class,
        KataChallengeEloquent::class,
    ],
    'max-seconds' => 3,
    'max-iterations' => 100,
    'save-outputs' => false,
    'show-hints' => false,
    'show-extended-scores' => true,
    'show-code-snippets' => true,
];
