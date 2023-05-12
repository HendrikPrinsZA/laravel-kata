<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_factory()
    {
        User::factory()->count(10)->create();
        $this->assertCount(10, User::all());
    }
}
