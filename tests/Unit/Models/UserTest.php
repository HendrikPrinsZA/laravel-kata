<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_factory()
    {
        $userCount = User::count();
        User::factory()->count(10)->create();
        $this->assertCount($userCount + 10, User::all());
    }
}
