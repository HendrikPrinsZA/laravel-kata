<?php

namespace App\Challenges\A;

use App\Challenges\Modules\FxConversionModule;
use App\Enums\CurrencyCode;
use App\KataChallenge;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * Test some approaches for fx conversions
 *
 * A. Fetch each rate from db
 * B. Fetch each rate from db and cache
 * C. Fetch each rate from db and cache in chunks of (month/year)
 * D. Cache all rates (extreme?)
 *
 * General notes
 * - New rates should be retained in cache for max 24hrs
 */
class FxConversion extends KataChallenge
{
    protected const DATE_FROM = '2023-01-01';

    protected const DATE_TO = '2024-01-01';

    protected const BASE_CURRENCY_CODE = CurrencyCode::EUR;

    protected const TARGET_CURRENCY_CODE = CurrencyCode::USD;

    public function useScriptCache(int $iteration): float
    {
        Config::set('modules.fx-conversion.options.script-caching.enabled', false);
        Config::set('modules.fx-conversion.options.script-caching.strategy', 'monthly');
        Config::set('modules.fx-conversion.options.global-caching.enabled', false);

        return $this->calculateTotalExchangeRate($iteration);
    }

    protected function calculateTotalExchangeRate(int $iteration, bool $useSingleton = false): float
    {
        $amount = 420.69;
        $total = 0;

        $dateFrom = Carbon::createFromFormat('Y-m-d', self::DATE_FROM);
        $dateTo = $dateFrom->copy()->addDays($iteration);

        $dateToMax = Carbon::createFromFormat('Y-m-d', self::DATE_TO);
        if ($dateTo > $dateToMax) {
            $dateTo = $dateToMax;
        }

        $fxConversionModule = $useSingleton ? FxConversionModule::make() : null;

        $dates = [];
        $carbonPeriod = CarbonPeriod::create($dateFrom, $dateTo);
        foreach ($carbonPeriod as $date) {
            $dates[] = $date;
            $total += $useSingleton
                ? $fxConversionModule->convert(self::BASE_CURRENCY_CODE, self::TARGET_CURRENCY_CODE, $date, $amount)
                : FxConversionModule::convert(self::BASE_CURRENCY_CODE, self::TARGET_CURRENCY_CODE, $date, $amount);
        }

        // No do it in reverse
        while (! empty($dates)) {
            $date = array_pop($dates);
            $total += $useSingleton
                ? $fxConversionModule->convert(self::BASE_CURRENCY_CODE, self::TARGET_CURRENCY_CODE, $date, $amount)
                : FxConversionModule::convert(self::BASE_CURRENCY_CODE, self::TARGET_CURRENCY_CODE, $date, $amount);
        }

        return $total;
    }
}
