<?php

namespace App\Enums;

use App\Models\Currency;
use App\Traits\EnumTrait;

enum CountryCode: string
{
    use EnumTrait;

    case AE = 'AE';
    case NL = 'NL';
    case UK = 'UK';
    case US = 'US';
    case ZA = 'ZA';

    public function details(): array
    {
        return match ($this) {
            self::AE => [
                'code' => $this->value,
                'name' => 'Dubai',
                'currency_id' => Currency::firstWhere('code', CurrencyCode::AED)?->id,
            ],
            self::NL => [
                'code' => $this->value,
                'name' => 'The Netherlands',
                'currency_id' => Currency::firstWhere('code', CurrencyCode::EUR)?->id,
            ],
            self::UK => [
                'code' => $this->value,
                'name' => 'United Kingdom',
                'currency_id' => Currency::firstWhere('code', CurrencyCode::GBP)?->id,
            ],
            self::US => [
                'code' => $this->value,
                'name' => 'United States',
                'currency_id' => Currency::firstWhere('code', CurrencyCode::USD)?->id,
            ],
            self::ZA => [
                'code' => $this->value,
                'name' => 'South Africa',
                'currency_id' => Currency::firstWhere('code', CurrencyCode::ZAR)?->id,
            ],
        };
    }
}
