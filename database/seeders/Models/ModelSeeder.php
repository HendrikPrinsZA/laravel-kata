<?php

namespace Database\Seeders\Models;

use Illuminate\Database\Seeder;

abstract class ModelSeeder extends Seeder
{
    public function run()
    {
        $this->seed();
        $this->cleanup();
    }

    abstract public function seed(): void;

    protected function cleanup(): void {}
}
