<?php

namespace App\Http\Controllers\Api;

use App\Services\GainsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GainsController extends ApiController
{
    protected ?string $challenge = null;

    protected ?string $method = null;

    // TODO: Move to custom Request
    public function __construct(
        protected Request $request,
        protected GainsService $gainsService
    ) {
        $parts = collect(explode('/', $this->request->path()))
            ->skip(2);

        if ($parts->isNotEmpty()) {
            $this->challenge = $parts->shift();
        }

        if ($parts->isNotEmpty()) {
            $this->method = $parts->shift();
        }
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->gainsService->getGains(
                $this->challenge,
                $this->method
            ),
        ]);
    }
}
