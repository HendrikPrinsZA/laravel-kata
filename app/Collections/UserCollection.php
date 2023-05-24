<?php

namespace App\Collections;

use Vendorize\LaravelPlus\Collections\SmartCollection;

class UserCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'email',
    ];
}
