<?php

namespace App\Http\Controllers\Api;

use App\Services\KataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KataController extends ApiController
{
    protected ?string $challenge = null;

    protected ?string $method = null;

    // TODO: Move to custom Request
    public function __construct(
        protected Request $request,
        protected KataService $kataService
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
        // Run the method
        if (! is_null($this->method) && ! is_null($this->challenge)) {
            return response()->json([
                'success' => true,
                'data' => $this->kataService->runChallengeMethod(
                    $this->request,
                    $this->challenge,
                    $this->method
                ),
            ]);
        }

        $challenges = $this->kataService->getChallenges();

        if (! is_null($this->challenge)) {
            $challenges = $challenges->filter(fn (string $challenge) => $challenge === $this->challenge);
        }

        $challenges = $challenges->map(fn (string $challenge) => [
            'challenge' => $challenge,
            'methods' => $this->kataService->getChallengeMethods($challenge)
                ->filter(fn (string $method) => is_null($this->method) || $method === $this->method)
                ->values(),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $challenges,
        ]);
    }
}
