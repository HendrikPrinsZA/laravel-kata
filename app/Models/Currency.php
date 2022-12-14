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

    /**
     * Required for collection::upsert()
     */
    public string $collection_unique_attributes = 'code';

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }
}
