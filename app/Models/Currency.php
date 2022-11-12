<?php

namespace App\Models;

use App\Enums\CurrencyCode;
use App\Traits\HasCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory, HasCollection;

    protected $fillable = [
        'code',
        'name',
    ];

    protected $casts = [
        'code' => CurrencyCode::class,
    ];
}
