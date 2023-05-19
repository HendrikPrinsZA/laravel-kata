<?php

namespace App\Kata\Challenges;

class KataChallengeMySQLRecord extends KataChallengeMySQL
{
    public function orVersusIn(int $limit): array
    {
        $sql = <<<'SQL'
        SELECT
        E.target_currency_code AS `code`,
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.date > DATE(:dateFrom) AND
        E.target_currency_code IN ('AED', 'EUR', 'GBP', 'USD', 'ZAR')
        GROUP BY E.target_currency_code
        SQL;

        $params = [
            'dateFrom' => now()->subDays($limit),
        ];

        $value = $this->select($sql, $params);

        return $this->return($value);
    }
}
