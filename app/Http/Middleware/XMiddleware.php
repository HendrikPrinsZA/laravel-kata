<?php

namespace App\Http\Middleware;

use App\Enums\AppID;
use App\Utilities\AppIDUtility;
use BenSampo\Enum\Exceptions\InvalidEnumMemberException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class XMiddleware
{
    public function __construct() { }

    public function handle(Request $request, Closure $next, ?int $seconds = null): mixed
    {
        return $next($request);
    }
}
