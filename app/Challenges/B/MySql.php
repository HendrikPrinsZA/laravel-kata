<?php

namespace App\Challenges\B;

use App\Challenges\A\MySql as AMySql;

class MySql extends AMySql
{
    public function orVersusIn(int $iteration): array
    {
        $sql = <<<'SQL'
        SELECT
        E.target_currency_code AS `code`,
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.id <= :limit AND
        E.target_currency_code IN ('AED', 'EUR', 'GBP', 'USD', 'ZAR')
        GROUP BY E.target_currency_code
        SQL;

        $params = [
            'limit' => $iteration,
        ];

        $value = $this->select($sql, $params);

        return $this->return($value);
    }

    public function orVersusInAggregate(int $iteration): float
    {
        $sql = <<<'SQL'
        SELECT
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.id <= :limit AND
        E.target_currency_code IN ('AED', 'EUR', 'GBP', 'USD', 'ZAR')
        SQL;

        $params = [
            'limit' => $iteration,
        ];

        $value = $this->selectOne($sql, $params)->rate;

        return $this->return($value);
    }
}
