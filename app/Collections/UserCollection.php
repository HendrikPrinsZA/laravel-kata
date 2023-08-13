<?php

namespace App\Collections;

use Larawell\LaravelPlus\Collections\SmartCollection;

class UserCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'email',
    ];
}
