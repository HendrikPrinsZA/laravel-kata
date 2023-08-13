<?php

namespace App\Collections;

use Larawell\LaravelPlus\Collections\SmartCollection;

class ExchangeRateCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'base_currency_id',
        'target_currency_id',
        'date',
    ];
}
