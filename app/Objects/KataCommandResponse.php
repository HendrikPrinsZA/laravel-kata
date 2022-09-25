<?php

namespace App\Objects;

class KataCommandResponse
{
    public function __construct(
        public float $duration,
        public mixed $response,
    ) {
    }


}
