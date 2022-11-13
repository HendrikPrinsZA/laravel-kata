<?php

namespace App\Services;

use App\Collections\ExchangeRateCollection;
use App\Enums\CurrencyCode;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected const API_HOST = 'https://api.exchangerate.host';

    public function syncCurrencies(): void
    {
        $codes = collect(CurrencyCode::cases())->pluck('value');
        $response = Http::get(sprintf('%s/symbols', self::API_HOST));
        $symbols = collect($response->json('symbols'))
            ->filter(fn ($symbol) => $codes->contains($symbol['code']));

        foreach ($symbols as $symbol) {
            $code = $symbol['code'];
            $name = $symbol['description'];

            $currency = Currency::firstWhere('code', $code);
            if (! is_null($currency)) {
                $currency->name = $name;
                $currency->save();

                continue;
            }

            $currency = Currency::factory()->make([
                'code' => CurrencyCode::from($code),
                'name' => $name,
            ]);
            $currency->save();
        }
    }

    public function syncExchangeRates(): void
    {
        $dateStart = ExchangeRate::max('date') ?? now()->subYears(10)->toDateString();
        $dateStart = Carbon::createFromFormat('Y-m-d', $dateStart);
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

    protected function syncExchangeRatesPeriod(Carbon $startDate, Carbon $endDate): void
    {
        $codes = collect(CurrencyCode::cases())
            ->filter(fn (CurrencyCode $currencyCode) => $currencyCode !== CurrencyCode::EUR)
            ->pluck('value');

        $url = sprintf(
            '%s/timeseries?start_date=%s&end_date=%s&base=%s&symbols=USD,ZAR',
            self::API_HOST,
            $startDate->toDateString(),
            $endDate->toDateString(),
            CurrencyCode::EUR->value,
            $codes->join(',')
        );

        $currencyLookup = [
            CurrencyCode::EUR->value => Currency::firstWhere('code', CurrencyCode::EUR),
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
                    'date' => $date->toDateString(),
                    'rate' => $rate,
                ]));
            }
        }

        $exchangeRates->chunk(500)->each(fn ($exchangeRatesChunk) => ExchangeRate::upsert($exchangeRatesChunk->toArray(), [
            'base_currency_id',
            'target_currency_id',
            'date',
        ], [
            'rate',
        ])
        );
    }
}
