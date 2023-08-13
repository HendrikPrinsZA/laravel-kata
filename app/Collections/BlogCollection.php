<?php

namespace App\Collections;

use Larawell\LaravelPlus\Collections\SmartCollection;

class BlogCollection extends SmartCollection
{
    protected const UNIQUE_FIELDS = [
        'user_id',
        'title',
    ];
}
