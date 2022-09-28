<?php

use App\Http\Controllers\KataController;
use App\Http\Resources\KataRunResponseResource;
use App\Kata\KataRunner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Console\Output\BufferedOutput;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/kata/before', function (Request $request) {
    $maxIterations = $request->get('max-iterations', 1000);

    /** @var KataRunner $kataRunner */
    $kataRunner = app(KataRunner::class, [
        'command' => null,
        'maxIterations' => $maxIterations,
        'mode' => 'before'
    ]);

    $results = $kataRunner->run();

    return JsonResource::make($results);
});

Route::get('/kata/after', function (Request $request) {
    $maxIterations = $request->get('max-iterations', 1000);

    // sleep(1);

    /** @var KataRunner $kataRunner */
    $kataRunner = app(KataRunner::class, [
        'command' => null,
        'maxIterations' => $maxIterations,
        'mode' => 'after'
    ]);

    $results = $kataRunner->run();

    return JsonResource::make($results);
});
