<?php

namespace App\Models;

use App\Enums\KataRunMode;
use App\Enums\KataRunnerPhase;
use Illuminate\Database\Eloquent\Model;

class KataReport extends Model
{
    protected $fillable = [
        'name',
        'php_version',
        'laravel_version',
        'max_duration',
        'max_iterations',
        'mode',
        'class',
        'method',
        'phase',

        // Stats
        'max_duration_iterations',
        'max_iterations_duration',
    ];

    protected $casts = [
        'mode' => KataRunMode::class,
        'phase' => KataRunnerPhase::class,
        'max_duration' => 'float',
        'max_iterations' => 'integer',
        'max_duration_iterations' => 'integer',
        'max_iterations_duration' => 'float',
    ];
}
