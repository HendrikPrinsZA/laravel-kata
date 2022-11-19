<?php

namespace App\Kata\Challenges;

use App\Kata\KataChallenge;
use Illuminate\Support\Facades\DB;

class KataChallengeMySQL extends KataChallenge
{
    protected function setUp(): void
    {
        $this->maxIterations = 3;
        $this->maxSeconds = 3;
    }

    public function baseline(): void
    {
    }

    /**
     * Get row count
     */
    public function getSample(int $limit): int
    {
        $sql = 'SELECT COUNT(*) AS `count`, SLEEP(1) FROM users WHERE id <= :id';
        $params = [
            'id' => $limit,
        ];

        return $this->select($sql, $params)[0]->count;
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
