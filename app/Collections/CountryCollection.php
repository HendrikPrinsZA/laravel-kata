<?php

namespace App\Collections;

use Vendorize\LaravelPlus\Collections\SmartCollection;

class CountryCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'code',
    ];
}
