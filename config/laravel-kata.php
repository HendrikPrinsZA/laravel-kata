<?php

use App\Kata\Challenges\KataChallengeEloquent;
use App\Kata\Challenges\KataChallengeMySQL;
use App\Kata\Challenges\KataChallengePhp;
use App\Kata\Challenges\KataChallengeSample;
use App\Kata\Enums\KataRunMode;
use App\Kata\Exceptions\KataInvalidRunModeException;

$runMode = KataRunMode::tryFrom(env('LK_RUN_MODE', 'benchmark'));
if (is_null($runMode)) {
    throw new KataInvalidRunModeException(sprintf(
        'Invalid run mode: %s',
        $runMode
    ));
}

$defaults = match ($runMode) {
    KataRunMode::DEBUG => [
        'APP_DEBUG' => true,

        'LK_MAX_SECONDS' => 1,
        'LK_MAX_ITERATIONS' => 1,

        'LK_DD_MAX_USERS' => 100,
        'LK_DD_MAX_USER_BLOGS' => 3,
    ],
    KataRunMode::BENCHMARK => [
        'LK_MAX_SECONDS' => 3,
        'LK_MAX_ITERATIONS' => 1000,

        'LK_DD_MAX_USERS' => 1000,
        'LK_DD_MAX_USER_BLOGS' => 10,
    ],
    KataRunMode::TEST => [
        'LK_MAX_SECONDS' => 0,
        'LK_MAX_ITERATIONS' => 1,

        'LK_DD_MAX_USERS' => 2,
        'LK_DD_MAX_USER_BLOGS' => 2,
    ]
};

$getValue = fn (string $key, mixed $dafault = null) => env($key, $defaults[$key] ?? $dafault);

return [
    'challenges' => [
        KataChallengeSample::class,
        KataChallengePhp::class,
        KataChallengeEloquent::class,
        KataChallengeMySQL::class,
    ],
    'max-seconds' => $getValue('LK_MAX_SECONDS', 3),
    'max-iterations' => $getValue('LK_MAX_ITERATIONS', 1000),
    'show-hints' => false,
    'show-hints-extended' => false,
    'save-outputs' => $getValue('APP_DEBUG', false),
    'show-extended-scores' => $getValue('APP_DEBUG', false),
    'show-code-snippets' => $getValue('APP_DEBUG', false),
    'debug-mode' => $getValue('APP_DEBUG', false),

    // Configuration of the dummy data
    // - Will allow to separate between benchmark & tests
    'dummy-data' => [
        'max-users' => $getValue('LK_DD_MAX_USERS', 1000),
        'max-user-blogs' => $getValue('LK_DD_MAX_USER_BLOGS', 3),
    ],
];
