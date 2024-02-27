<?php

/**
 * @label Sample Snippet
 * @description Gets the count of users and the current environment
 */

use App\Models\User;

dump([
    "users" => User::count(),
    "env" => app()->environment()
]);
