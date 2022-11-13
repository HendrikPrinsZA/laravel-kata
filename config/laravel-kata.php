<?php

use App\Kata\Challenges\KataChallengeEloquent;
use App\Kata\Challenges\KataChallengePhp;
use App\Kata\Challenges\KataChallengeSample;

return [
    'challenges' => [
        // KataChallengeSample::class,
        KataChallengePhp::class,
        // KataChallengeEloquent::class,
        // KataChallengeMySQL::class,
    ],
    'max-seconds' => 3,
    'max-iterations' => 1000,
    'show-hints' => false,
    'show-hints-extended' => false,
    'save-outputs' => env('APP_DEBUG', false),
    'show-extended-scores' => env('APP_DEBUG', false),
    'show-code-snippets' => env('APP_DEBUG', false),
    'debug-mode' => env('APP_DEBUG', false),
];
