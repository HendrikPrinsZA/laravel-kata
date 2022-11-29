<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum CurrencyCode: string
{
    use EnumTrait;

    case AED = 'AED';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case USD = 'USD';
    case ZAR = 'ZAR';
}
