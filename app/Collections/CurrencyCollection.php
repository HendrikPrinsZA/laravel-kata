<?php

namespace App\Collections;

use Larawell\LaravelPlus\Collections\SmartCollection;

class CurrencyCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'code',
    ];
}
