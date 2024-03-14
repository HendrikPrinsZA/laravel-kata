<?php

use App\Challenges\A\CleanCode;
use App\Challenges\A\CleanCodeDatabase;
use App\Challenges\A\Eloquent;
use App\Challenges\A\FxConversion;
use App\Challenges\A\Laravel;
use App\Challenges\A\MySql;
use App\Challenges\A\Php;
use App\Challenges\A\Sample;
use App\Challenges\A\Silly;
use App\Enums\KataRunMode;
use App\Enums\KataRunnerIterationMode;
use App\Exceptions\KataChallengeScoreOutputsMd5Exception;
use App\Exceptions\KataInvalidRunModeException;

$runMode = KataRunMode::tryFrom(env('LK_RUN_MODE', KataRunMode::DEBUG->value));
if (is_null($runMode)) {
    throw new KataInvalidRunModeException(sprintf('Invalid run mode: %s', $runMode));
}

$defaults = match ($runMode) {
    KataRunMode::DEBUG => [
        'LK_MAX_SECONDS' => 1,
        'LK_MAX_ITERATIONS' => 1,

        'LK_DD_MAX_USERS' => 1,
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

        'LK_DD_MAX_USERS' => 10,
        'LK_DD_MAX_USER_BLOGS' => 2,
    ],
};

$getValue = fn (string $key, mixed $dafault = null) => env($key, $defaults[$key] ?? $dafault);

return [
    'challenges' => [
        Sample::class,
        Php::class,
        Eloquent::class,
        MySql::class,
        Laravel::class,
        CleanCode::class,
        CleanCodeDatabase::class,
        Silly::class,
        FxConversion::class,
    ],

    'ignore-exceptions' => [
        KataChallengeScoreOutputsMd5Exception::class,
    ],

    // Params
    'max-seconds' => $getValue('LK_MAX_SECONDS'),
    'max-iterations' => $getValue('LK_MAX_ITERATIONS'),
    'max-iterations-max-seconds' => 60,
    'progress-bar-disabled' => env('LK_PROGRESS_BAR_DISABLED', false),

    'modes' => [
        KataRunnerIterationMode::MAX_ITERATIONS,
        KataRunnerIterationMode::MAX_SECONDS,
        KataRunnerIterationMode::XDEBUG_PROFILE,
    ],

    // To be converted to env variables
    'save-results-to-storage' => true,
    'debug-mode' => false,
    'show-hints' => false,
    'show-hints-extended' => false,
    'show-code-snippets' => false,
    'gains-perc-minimum' => -100, // We don't care about negative gains :)

    // Experimental (not stable)
    'experimental' => [
        'cache-results' => false,
    ],

    // Configuration of the dummy data
    // - Will allow to separate between benchmark & tests
    'dummy-data' => [
        'max-users' => env('LK_DD_MAX_USERS', $getValue('LK_DD_MAX_USERS', 1000)),
        'max-user-blogs' => env('LK_DD_MAX_USER_BLOGS', $getValue('LK_DD_MAX_USER_BLOGS', 3)),
    ],
];
