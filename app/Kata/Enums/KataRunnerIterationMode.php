<?php

namespace App\Kata\Enums;

enum KataRunnerIterationMode: string
{
    case MAX_ITERATIONS = 'max-iterations';
    case MAX_SECONDS = 'max-seconds';
}
