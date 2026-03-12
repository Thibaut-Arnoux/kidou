<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\MilkActivityController;
use App\Http\Controllers\Api\V1\MilkGoalController;
use App\Http\Controllers\Api\V1\MilkMeasureController;
use App\Http\Middleware\InjectDemoBaby;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn (): JsonResponse => response()->json([
    'status' => 'healthy',
    'timestamp' => Illuminate\Support\Facades\Date::now()->toIso8601String(),
]))->name('api.v1.health');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request): mixed => $request->user())->name('api.v1.user');
});

// TODO: add auth:sanctum middleware once authorization is in place
Route::middleware(InjectDemoBaby::class)->group(function (): void {
    Route::get('categories', [CategoryController::class, 'index'])->name('api.v1.categories.index');
    Route::get('milk-activity', MilkActivityController::class);
    Route::apiResource('milk-goals', MilkGoalController::class);
    Route::apiResource('milk-goals.measures', MilkMeasureController::class)->scoped();
});
