<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use Illuminate\Support\Facades\DB;

class KataChallengeMySQL extends KataChallenge
{
    public function baseline(): void
    {
    }

    public function getRecordsBasedOnDateRange(int $limit): int
    {
        $sql = <<<SQL
SELECT *
FROM exchange_rates E
WHERE
E.date LIKE CONCAT(YEAR(NOW()) - 1, '-%')
LIMIT $limit
SQL;

        $rows = $this->select($sql);

        return count($rows);
    }

    protected function select(string $sql, array $params = []): array
    {
        $sql = str_replace('SELECT', 'SELECT SQL_NO_CACHE', $sql);

        return DB::select(DB::raw($sql), $params);
    }
}
