<?php

namespace App\Services;

use App\Enums\CurrencyCode;
use App\Models\Currency;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    public function syncCurrencies(): void
    {
        $codes = collect(CurrencyCode::cases())->pluck('value');
        $response = Http::get('https://api.exchangerate.host/symbols');
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
}
