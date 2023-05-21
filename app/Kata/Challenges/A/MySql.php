<?php

namespace App\Kata\Challenges\A;

use App\Kata\KataChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MySql extends KataChallenge
{
    public function orVersusIn(int $limit): array
    {
        $sql = <<<'SQL'
        SELECT
        E.target_currency_code AS `code`,
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.id <= :limit AND
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
            'limit' => $limit,
        ];

        $value = $this->select($sql, $params);

        return $this->return($value);
    }

    public function orVersusInAggregate(int $limit): float
    {
        $sql = <<<'SQL'
        SELECT
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.id <= :limit AND
        (
            E.target_currency_code = 'AED' OR
            E.target_currency_code = 'EUR' OR
            E.target_currency_code = 'GBP' OR
            E.target_currency_code = 'USD' OR
            E.target_currency_code = 'ZAR'
        )
        SQL;

        $params = [
            'limit' => $limit,
        ];

        $value = $this->selectOne($sql, $params)->rate;

        return $this->return($value);
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
