<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AchievementCategoryController;
use App\Http\Controllers\Api\V1\AchievementController;
use App\Http\Controllers\Api\V1\BabyAchievementController;
use App\Http\Controllers\Api\V1\MilkActivityController;
use App\Http\Controllers\Api\V1\MilkGoalController;
use App\Http\Controllers\Api\V1\MilkMeasureController;
use App\Http\Controllers\Api\V1\StoreBabyController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn (): JsonResponse => response()->json([
    'status' => 'healthy',
    'timestamp' => Illuminate\Support\Facades\Date::now()->toIso8601String(),
]))->name('api.v1.health');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request): mixed => $request->user())->name('api.v1.user');

    Route::post('babies', StoreBabyController::class)->name('babies.store');
});

Route::middleware(['auth:sanctum', 'resolve.active.baby'])->group(function (): void {
    // Achievement categories & achievements (read-only)
    Route::apiResource('achievement-categories', AchievementCategoryController::class)
        ->only(['index']);

    Route::apiResource('achievements', AchievementController::class)
        ->only(['index']);

    // Baby achievements
    Route::post('baby-achievements/{achievement}', [BabyAchievementController::class, 'store'])
        ->name('baby-achievements.store');

    Route::apiResource('baby-achievements', BabyAchievementController::class)
        ->only(['index', 'update', 'destroy']);

    // Milk tracking
    Route::get('milk-activity', MilkActivityController::class)->name('milk-activity.index');

    Route::apiResource('milk-goals', MilkGoalController::class);
    Route::apiResource('milk-goals.measures', MilkMeasureController::class)->scoped();
});
