<?php

namespace App\Collections;

use Vendorize\LaravelPlus\Collections\SmartCollection;

class CurrencyCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'code',
    ];
}
