<?php

namespace App\Kata\Challenges;

class KataChallengeMySQLRecord extends KataChallengeMySQL
{
    public function getSample(int $limit): int
    {
        $sql = 'SELECT COUNT(id) AS `count` FROM users WHERE id <= :id';
        $params = [
            'id' => $limit,
        ];

        return $this->select($sql, $params)[0]->count;
    }

    public function getRecordsBasedOnDateRange(int $limit): int
    {
        $sql = "SELECT * FROM exchange_rates WHERE date BETWEEN CAST(:startDate AS DATE) AND CAST(:endDate AS DATE) LIMIT $limit;";
        $params = [
            'startDate' => now()->subYear()->startOfYear()->toDateString(),
            'endDate' => now()->subYear()->endOfYear()->toDateString(),
        ];
        $rows = $this->select($sql, $params);

        return count($rows);
    }
}
