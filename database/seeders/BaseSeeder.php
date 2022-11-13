<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

abstract class BaseSeeder extends Seeder
{
    public function run()
    {
        $this->seed();
        $this->cleanup();
    }

    abstract public function seed(): void;

    protected function cleanup(): void
    {
    }
}
