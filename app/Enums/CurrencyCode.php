<?php

namespace App\Enums;

use App\Models\Currency;
use App\Traits\EnumTrait;

enum CurrencyCode: string
{
    use EnumTrait;

    case AED = 'AED';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case USD = 'USD';
    case ZAR = 'ZAR';

    public function getModel(): Currency
    {
        return Currency::query()->where('code', $this->value)->first();
    }
}
