<?php

use App\Models\Currency;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

Route::get('exchangerate/timeframe', function (Request $request) {
    $startDate = $request->get('start_date');
    $endDate = $request->get('end_date');
    $source = $request->get('source');
    $currencyCodes = explode(',', $request->get('currencies'));

    $sourceCurrency = Currency::where('code', $source)->firstOrFail();
    $targetCurrencies = Currency::whereIn('code', $currencyCodes)->get();

    $quotes = [];
    $carbonPeriod = CarbonPeriod::create($startDate, $endDate);
    foreach ($carbonPeriod as $date) {
        $day = $date->format('Y-m-d');
        $quotes[$day] = [];
        foreach ($targetCurrencies as $targetCurrency) {
            $key = sprintf('%s%s', $sourceCurrency->code->value, $targetCurrency->code->value);

            $rate = $date->timestamp / ($sourceCurrency->id / $targetCurrency->id);
            $quotes[$day][$key] = round($rate / 3000000000, 10);
        }
    }

    return JsonResource::make([
        'success' => true,
        'terms' => 'https://localhost/mock/exchangerate/terms',
        'privacy' => 'https://localhost/mock/exchangerate/privacy',
        'timeframe' => true,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'source' => $source,
        'quotes' => $quotes,
    ]);
});
