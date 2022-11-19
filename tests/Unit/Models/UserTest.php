<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory()
    {
        User::all()->each(fn (User $user) => $user->delete());
        User::factory()->count(10)->create();
        $this->assertCount(10, User::all());
    }
}
