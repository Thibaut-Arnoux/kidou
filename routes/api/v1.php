<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BabyAchievementController;
use App\Http\Controllers\Api\V1\BabyController;
use App\Http\Controllers\Api\V1\ListAchievementController;
use App\Http\Controllers\Api\V1\ListCategoryController;
use App\Http\Controllers\Api\V1\MilkActivityController;
use App\Http\Controllers\Api\V1\MilkGoalController;
use App\Http\Controllers\Api\V1\MilkMeasureController;
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

Route::get('categories', ListCategoryController::class)->name('categories.index');
Route::get('achievements', ListAchievementController::class)->name('achievements.index');

// TODO: add auth:sanctum middleware to all routes once authorization is in place
Route::middleware('auth:sanctum')->apiResource('babies', BabyController::class);

Route::apiResource('babies.milk-goals', MilkGoalController::class)->shallow();
Route::apiResource('milk-goals.measures', MilkMeasureController::class)->scoped();

Route::prefix('babies/{baby}')->group(function (): void {
    Route::get('milk-activity', MilkActivityController::class)->name('babies.milk-activity.index');
});

// Baby achievements: shallow — index/store nested under baby, update/destroy standalone
Route::get('babies/{baby}/achievements', [BabyAchievementController::class, 'index'])->name('babies.achievements.index');
Route::post('babies/{baby}/achievements', [BabyAchievementController::class, 'store'])->name('babies.achievements.store');
Route::put('baby-achievements/{babyAchievement}', [BabyAchievementController::class, 'update'])->name('baby-achievements.update');
Route::delete('baby-achievements/{babyAchievement}', [BabyAchievementController::class, 'destroy'])->name('baby-achievements.destroy');
