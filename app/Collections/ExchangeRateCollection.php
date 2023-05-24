<?php

namespace App\Collections;

use Vendorize\LaravelPlus\Collections\SmartCollection;

class ExchangeRateCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'base_currency_id',
        'target_currency_id',
        'date',
    ];
}
