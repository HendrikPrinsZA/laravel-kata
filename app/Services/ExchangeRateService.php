<?php

namespace App\Services;

use App\Collections\CurrencyCollection;
use App\Collections\ExchangeRateCollection;
use App\Enums\CurrencyCode;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected const MAX_YEARS = 5;

    protected string $apiHost;

    protected string $apiKey;

    public function __construct()
    {
        $this->apiHost = config('exchange-rates.api-host');
        $this->apiKey = config('exchange-rates.api-key');
    }

    public function getCurrencies(): CurrencyCollection
    {
        $currencies = collect(CurrencyCode::cases())
            ->map(fn (CurrencyCode $currencyCode) => Currency::make([
                'code' => $currencyCode,
                'name' => $currencyCode->value,
            ]));

        return CurrencyCollection::make($currencies);
    }

    public function syncExchangeRates(?int $maxYears = null): void
    {
        $dateStart = ExchangeRate::max('date') ?? now()->subYears($maxYears ?? self::MAX_YEARS)->toDateString();
        $dateStart = Carbon::createFromFormat('Y-m-d', $dateStart);

        // Check from yesterday
        $dateEnd = now()->subDay();

        // Skip, because we have the latest data
        if ($dateStart->gt($dateEnd)) {
            return;
        }

        $period = CarbonPeriod::create($dateStart, '1 year', $dateEnd);

        foreach ($period as $startDate) {
            $this->syncExchangeRatesPeriod($startDate, $startDate->copy()->addYear());
        }
    }

    private function syncExchangeRatesPeriod(Carbon $startDate, Carbon $endDate): void
    {
        // Get the currency codes
        // - The base is EUR, so filter out
        $codes = collect(CurrencyCode::cases())
            ->filter(fn (CurrencyCode $currencyCode) => $currencyCode !== CurrencyCode::EUR)
            ->pluck('value');

        $url = sprintf(
            '%s/timeframe?access_key=%s&start_date=%s&end_date=%s&source=%s&currencies=%s',
            $this->apiHost,
            $this->apiKey,
            $startDate->toDateString(),
            $endDate->toDateString(),
            CurrencyCode::EUR->value,
            $codes->join(',')
        );

        $currencyLookup = Currency::whereIn('code', CurrencyCode::cases())->get()
            ->keyBy(fn (Currency $currency) => $currency->code->value)
            ->toArray();

        $rates = $this->getRates($url);
        $exchangeRates = ExchangeRateCollection::make();
        foreach ($rates as $rawDate => $rawRates) {
            $date = Carbon::createFromFormat('Y-m-d', $rawDate);
            foreach ($rawRates as $currencyCode => $rate) {
                $currencyCode = substr($currencyCode, 3);
                $exchangeRates->push(ExchangeRate::factory()->makeOne([
                    'base_currency_id' => $currencyLookup[CurrencyCode::EUR->value]['id'],
                    'target_currency_id' => $currencyLookup[$currencyCode]['id'],
                    'target_currency_code' => $currencyLookup[$currencyCode]['code'],
                    'date' => $date->toDateString(),
                    'rate' => $rate,
                ]));
            }
        }

        $exchangeRates->upsert();
    }

    public function getRates(string $url): array
    {
        $urlMd5 = md5($url);
        $cacheKey = sprintf('fx:%s', $urlMd5);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $rates = Http::get($url)->json('quotes');

        // Note: Used to reset the static cache (for testing)
        // $filepath = sprintf('%s/../../database/seeders/Models/Files/fx_%s.json', __DIR__, $urlMd5);
        // file_put_contents($filepath, json_encode($rates));

        Cache::set($cacheKey, $rates);

        return $rates;
    }
}
