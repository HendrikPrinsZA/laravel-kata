<?php

namespace App\Challenges\Modules;

use App\Enums\CurrencyCode;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Exception;
use Illuminate\Support\Carbon;

class FxConversionModule
{
    protected static array $cachedRates = [];

    public static function convert(
        CurrencyCode $baseCurrencyCode,
        CurrencyCode $targetCurrencyCode,
        Carbon $date,
        float $amount
    ): float {
        return $amount * self::getRate($baseCurrencyCode, $targetCurrencyCode, $date);
    }

    public static function getRate(
        CurrencyCode $baseCurrencyCode,
        CurrencyCode $targetCurrencyCode,
        Carbon $date
    ): float {
        if ($baseCurrencyCode === $targetCurrencyCode) {
            return 1;
        }

        $baseCurrency = $baseCurrencyCode->getModel();
        $targetCurrency = $targetCurrencyCode->getModel();

        if (config('modules.fx-conversion.options.script-caching.enabled')) {
            $cacheKey = sprintf('%d:%d:%s', $baseCurrency->id, $targetCurrency->id, $date->toDateString());

            if (isset(self::$cachedRates[$cacheKey]['rate'])) {
                return self::$cachedRates[$cacheKey]['rate'];
            }
        }

        return self::getRateFromDatabase($baseCurrency, $targetCurrency, $date);
    }

    protected static function getRateFromDatabase(
        Currency $baseCurrency,
        Currency $targetCurrency,
        Carbon $date
    ): float {
        $dateString = $date->toDateString();

        $exchangeRates = ExchangeRate::query()
            ->select('rate', 'date')
            ->where('base_currency_id', $baseCurrency->id)
            ->where('target_currency_id', $targetCurrency->id);

        $exchangeRates = match (config('modules.fx-conversion.options.script-caching.strategy')) {
            'daily' => $exchangeRates->where('date', $dateString),
            'monthly' => $exchangeRates->whereBetween('date', [
                $date->copy()->startOfMonth()->subMonth(),
                $date->copy()->addMonth()->endOfMonth(),
            ]),
            'yearly' => $exchangeRates->whereBetween('date', [
                $date->copy()->startOfMonth()->subMonth(),
                $date->copy()->addMonth()->endOfMonth(),
            ]),
            'all' => $exchangeRates,
        };

        $exchangeRates = $exchangeRates->get()->mapWithKeys(fn (ExchangeRate $exchangeRate) => [
            sprintf('%d:%d:%s', $baseCurrency->id, $targetCurrency->id, $exchangeRate->date->format('Y-m-d')) => [
                'rate' => $exchangeRate->rate,
                'monthly_rate_open' => $exchangeRate->monthly_rate_open,
                'monthly_rate_average' => $exchangeRate->monthly_rate_average,
                'monthly_rate_close' => $exchangeRate->monthly_rate_close,
            ],
        ])->toArray();

        $actualExchangeRateKey = sprintf('%d:%d:%s', $baseCurrency->id, $targetCurrency->id, $date->toDateString());
        $actualExchangeRate = $exchangeRates[$actualExchangeRateKey] ?? null;
        if (is_null($actualExchangeRate)) {
            throw new Exception(sprintf(
                'No exchange rate found for %s to %s on %s',
                $baseCurrency->code->value,
                $targetCurrency->code->value,
                $dateString
            ));
        }

        if (config('modules.fx-conversion.options.script-caching.enabled')) {
            self::$cachedRates = [
                ...self::$cachedRates,
                ...$exchangeRates,
            ];
        }

        return $actualExchangeRate['rate'];
    }
}
