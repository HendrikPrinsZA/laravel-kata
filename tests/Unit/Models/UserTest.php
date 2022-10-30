<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory()
    {
        User::factory()->count(10)->create();
        $this->assertCount(10, User::all());
    }
}
