<?php

namespace App\Kata\Challenges;

use App\Enums\CurrencyCode;

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

        return $this->select($sql, $params);
    }

    public function findRecordsBasedOnIndex(int $limit): array
    {
        $sql = <<<'SQL'
        SELECT
        E.target_currency_code AS `code`,
        AVG(E.rate) AS `rate`
        FROM exchange_rates E
        WHERE
        E.date > DATE(:dateFrom) AND
        E.target_currency_id = :target_currency_id
        GROUP BY E.target_currency_code
        SQL;

        $limit = $limit - 1;
        $sequenceId = ($limit % CurrencyCode::all()->count()) + 1;

        $params = [
            'dateFrom' => now()->subDays($limit),
            'target_currency_id' => $sequenceId,
        ];

        return $this->select($sql, $params);
    }
}
