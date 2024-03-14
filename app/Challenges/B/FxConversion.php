<?php

namespace App\Challenges\B;

use App\Challenges\A\FxConversion as AFxConversion;
use Illuminate\Support\Facades\Config;

class FxConversion extends AFxConversion
{
    public function useScriptCache(int $iteration): float
    {
        Config::set('modules.fx-conversion.options.script-caching.enabled', true);
        Config::set('modules.fx-conversion.options.script-caching.strategy', 'monthly');
        Config::set('modules.fx-conversion.options.global-caching.enabled', false);

        return $this->calculateTotalExchangeRate($iteration, true);
    }
}
