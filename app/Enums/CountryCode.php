<?php

namespace App\Enums;

use App\Collections\CurrencyCollection;
use App\Models\Currency;
use App\Traits\EnumTrait;
use Exception;

enum CountryCode: string
{
    use EnumTrait;

    case AE = 'AE';
    case NL = 'NL';
    case UK = 'UK';
    case US = 'US';
    case ZA = 'ZA';

    protected function getCurrencies(): CurrencyCollection
    {
        $currencies = Currency::all();
        if ($currencies->isEmpty()) {
            throw new Exception(sprintf('No currencies found for "%s"', $this->name));
        }

        return $currencies;
    }

    public function details(): array
    {
        $currencies = $this->getCurrencies();
        if ($currencies->isEmpty()) {
            throw new Exception('No currencies found');
        }

        return match ($this) {
            self::AE => [
                'code' => $this->value,
                'name' => 'Dubai',
                'currency_id' => $currencies->firstWhere('code', CurrencyCode::AED)->id,
            ],
            self::NL => [
                'code' => $this->value,
                'name' => 'The Netherlands',
                'currency_id' => $currencies->firstWhere('code', CurrencyCode::EUR)->id,
            ],
            self::UK => [
                'code' => $this->value,
                'name' => 'United Kingdom',
                'currency_id' => $currencies->firstWhere('code', CurrencyCode::GBP)->id,
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
