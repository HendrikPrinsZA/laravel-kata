<?php

namespace App\Enums;

enum KataRunMode: string
{
    case DEBUG = 'debug';
    case TEST = 'test';
    case BENCHMARK = 'benchmark';
}
