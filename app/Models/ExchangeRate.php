<?php

namespace App\Models;

use App\Traits\HasCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasCollection, HasFactory;

    protected $fillable = [
        'base_currency_id',
        'target_currency_id',
        'target_currency_code',
        'date',
        'rate',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'rate' => 'float',
    ];

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
