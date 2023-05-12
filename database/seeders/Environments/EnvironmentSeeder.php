<?php

namespace Database\Seeders\Environments;

use Illuminate\Database\Seeder;

abstract class EnvironmentSeeder extends Seeder
{
    abstract public function run(): void;
}
