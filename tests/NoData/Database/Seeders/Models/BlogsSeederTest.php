<?php

use App\Models\Blog;
use Database\Seeders\Models\BlogsSeeder;

it('can seed', function () {
    $this->seed(BlogsSeeder::class);

    expect(Blog::count())->toBeGreaterThan(0);
});
