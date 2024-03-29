<?php

namespace App\Enums;

enum KataRunnerIterationMode: string
{
    case MAX_ITERATIONS = 'max-iterations';
    case MAX_SECONDS = 'max-seconds';
    case XDEBUG_PROFILE = 'xdebug-profile';
}
