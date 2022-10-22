<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class XMiddleware
{
    public function __construct()
    {
    }

    public function handle(Request $request, Closure $next, ?int $seconds = null): mixed
    {
        return $next($request);
    }
}
