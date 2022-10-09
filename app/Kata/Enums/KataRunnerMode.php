<?php

namespace App\Kata\Enums;

enum KataRunnerMode: string
{
    case ALL = 'all';
    case BEFORE = 'before';
    case RECORD = 'record';
}
