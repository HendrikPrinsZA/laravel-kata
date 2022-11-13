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
}
