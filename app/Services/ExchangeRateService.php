<?php

namespace App\Services;

use App\Collections\CurrencyCollection;
use App\Collections\ExchangeRateCollection;
use App\Enums\CurrencyCode;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected const MAX_YEARS = 5;

    protected const API_HOST = 'https://api.exchangerate.host';

    public function getCurrencies(): CurrencyCollection
    {
        $codes = CurrencyCode::all()->pluck('code');
        $response = Http::get(sprintf('%s/symbols', self::API_HOST));
        $symbols = collect($response->json('symbols'))
            ->filter(fn ($symbol) => $codes->contains($symbol['code']));

        $currencies = CurrencyCollection::make();
        foreach ($symbols as $symbol) {
            $code = $symbol['code'];
            $name = $symbol['description'];

            $currency = Currency::factory()->make([
                'code' => CurrencyCode::from($code),
                'name' => $name,
            ]);
            $currencies->push($currency);
        }

        return $currencies;
    }

    public function syncExchangeRates(): void
    {
        $dateStart = ExchangeRate::max('date') ?? now()->subYears(self::MAX_YEARS)->toDateString();

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
            '%s/timeseries?start_date=%s&end_date=%s&base=%s&symbols=%s',
            self::API_HOST,
            $startDate->toDateString(),
            $endDate->toDateString(),
            CurrencyCode::EUR->value,
            $codes->join(',')
        );

        $currencyLookup = [
            CurrencyCode::AED->value => Currency::firstWhere('code', CurrencyCode::AED),
            CurrencyCode::EUR->value => Currency::firstWhere('code', CurrencyCode::EUR),
            CurrencyCode::GBP->value => Currency::firstWhere('code', CurrencyCode::GBP),
            CurrencyCode::USD->value => Currency::firstWhere('code', CurrencyCode::USD),
            CurrencyCode::ZAR->value => Currency::firstWhere('code', CurrencyCode::ZAR),
        ];

        $exchangeRates = ExchangeRateCollection::make();
        $rates = Http::get($url)->json('rates');

        foreach ($rates as $rawDate => $rawRates) {
            $date = Carbon::createFromFormat('Y-m-d', $rawDate);
            foreach ($rawRates as $currencyCode => $rate) {
                $exchangeRates->push(ExchangeRate::factory()->makeOne([
                    'base_currency_id' => $currencyLookup[CurrencyCode::EUR->value]->id,
                    'target_currency_id' => $currencyLookup[$currencyCode]->id,
                    'target_currency_code' => $currencyLookup[$currencyCode]->code,
                    'date' => $date->toDateString(),
                    'rate' => $rate,
                ]));
            }
        }

        $exchangeRates->upsert();
    }
}
