<?php

namespace App\Kata\Challenges;

use App\Enums\CurrencyCode;
use App\Kata\KataChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KataChallengeMySQL extends KataChallenge
{
    public function baseline(): void
    {
    }

    public function orVersusIn(int $limit): array
    {
        $sql = <<<'SQL'
        SELECT
        E.target_currency_code AS `code`,
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.date > DATE(:dateFrom) AND
        (
            E.target_currency_code = 'AED' OR
            E.target_currency_code = 'EUR' OR
            E.target_currency_code = 'GBP' OR
            E.target_currency_code = 'USD' OR
            E.target_currency_code = 'ZAR'
        )
        GROUP BY E.target_currency_code
        SQL;

        $params = [
            'dateFrom' => now()->subDays($limit),
        ];

        return $this->select($sql, $params);
    }

    /**
     * Faster by string or int?
     *
     * Note: Unreliable results
     */
    protected function findRecordsBasedOnIndex(int $limit): array
    {
        $sql = <<<'SQL'
        SELECT
        E.target_currency_code AS `code`,
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.date > DATE(:dateFrom) AND
        E.target_currency_code = :target_currency_code
        GROUP BY E.target_currency_code
        SQL;

        $limit = $limit - 1;
        $currencyCodes = CurrencyCode::all();
        $sequenceId = $limit % $currencyCodes->count();
        $currencyCode = $currencyCodes->get($sequenceId);

        $params = [
            'dateFrom' => now()->subDays($limit),
            'target_currency_code' => $currencyCode['code'],
        ];

        return $this->select($sql, $params);
    }

    protected function selectOne(string $sql, array $params = []): mixed
    {
        $sql = Str::replaceFirst('SELECT', 'SELECT SQL_NO_CACHE', $sql);
        $expression = DB::raw($sql);
        $parsedSql = $expression->getValue(DB::connection()->getQueryGrammar());

        return DB::selectOne($parsedSql, $params);
    }

    protected function select(string $sql, array $params = []): array
    {
        $sql = Str::replaceFirst('SELECT', 'SELECT SQL_NO_CACHE', $sql);
        $expression = DB::raw($sql);
        $parsedSql = $expression->getValue(DB::connection()->getQueryGrammar());

        return DB::select($parsedSql, $params);
    }
}
