<?php

namespace App\Models;

use App\Traits\HasCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory, HasCollection;

    protected $fillable = [
        'currency_id',
        'code',
        'name',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function exchangeRates(): HasMany
    {
        return $this->currency->exchangeRates();
    }

    public function setExchangeRatesAggregates(): void
    {
        $this->exchangeRatesAvg = (float) $this->exchangeRates()->avg('rate');
        $this->exchangeRatesSum = (float) $this->exchangeRates()->sum('rate');
        $this->exchangeRatesMin = (float) $this->exchangeRates()->min('rate');
        $this->exchangeRatesMax = (float) $this->exchangeRates()->max('rate');
        $this->exchangeRatesCount = (int) $this->exchangeRates()->count();
    }
}
