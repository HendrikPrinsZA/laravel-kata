<?php

namespace App\Models;

use App\Enums\CurrencyCode;
use App\Traits\HasCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(
            ExchangeRate::class,
            $this->code === CurrencyCode::EUR ? 'base_currency_id' : 'target_currency_id'
        );
    }
}
