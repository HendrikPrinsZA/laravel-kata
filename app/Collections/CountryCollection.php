<?php

namespace App\Collections;

use Larawell\LaravelPlus\Collections\SmartCollection;

class CountryCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'code',
    ];
}
