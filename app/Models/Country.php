<?php

namespace App\Models;

use App\Traits\HasCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Country extends Model
{
    use HasCollection, HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Required for collection::upsert()
     */
    public string $collection_unique_attributes = 'code';

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
