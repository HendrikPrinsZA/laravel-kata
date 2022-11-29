<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        User::all()->delete();
    }

    public function test_factory()
    {
        User::factory()->count(10)->create();
        $this->assertCount(10, User::all());
    }
}
