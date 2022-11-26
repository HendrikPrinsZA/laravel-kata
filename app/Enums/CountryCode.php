<?php

namespace App\Enums;

use App\Models\Currency;
use App\Traits\EnumTrait;
use Exception;

enum CountryCode: string
{
    use EnumTrait;

    case NL = 'NL';
    case US = 'US';
    case ZA = 'ZA';

    public function details(): array
    {
        $currencies = Currency::all();

        if ($currencies->isEmpty()) {
            throw new Exception('No currencies found');
        }

        return match ($this) {
            self::NL => [
                'code' => $this->value,
                'name' => 'The Netherlands',
                'currency_id' => $currencies->firstWhere('code', CurrencyCode::EUR)->id,
            ],
            self::US => [
                'code' => $this->value,
                'name' => 'United States',
                'currency_id' => $currencies->firstWhere('code', CurrencyCode::USD)->id,
            ],
            self::ZA => [
                'code' => $this->value,
                'name' => 'South Africa',
                'currency_id' => $currencies->firstWhere('code', CurrencyCode::ZAR)->id,
            ],
        };
    }
}
