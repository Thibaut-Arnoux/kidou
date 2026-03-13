<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\BabyAchievement\CreateBabyAchievement;
use App\Actions\BabyAchievement\DeleteBabyAchievement;
use App\Actions\BabyAchievement\ListBabyAchievements;
use App\Actions\BabyAchievement\UpdateBabyAchievement as UpdateBabyAchievementAction;
use App\Http\Requests\Api\V1\ListBabyAchievementRequest;
use App\Http\Requests\Api\V1\StoreBabyAchievementRequest;
use App\Http\Requests\Api\V1\UpdateBabyAchievementRequest;
use App\Http\Resources\BabyAchievementResource;
use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final readonly class BabyAchievementController
{
    public function index(ListBabyAchievementRequest $request, Baby $baby, ListBabyAchievements $action): AnonymousResourceCollection
    {
        $category = $request->validated('category')
            ? Category::query()->where('uuid', $request->validated('category'))->first()
            : null;

        return BabyAchievementResource::collection($action->handle($baby, $category));
    }

    public function store(StoreBabyAchievementRequest $request, Baby $baby, CreateBabyAchievement $action): JsonResponse
    {
        $achievement = Achievement::query()
            ->where('uuid', $request->validated('achievement_id'))
            ->first();

        $result = $action->handle(
            baby: $baby,
            achievement: $achievement,
            note: $request->validated('note'),
            uuid: $request->validated('uuid'),
        );

        if ($result->isErr()) {
            return response()->json([
                'message' => $result->error(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return BabyAchievementResource::make($result->value())
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateBabyAchievementRequest $request,
        BabyAchievement $babyAchievement,
        UpdateBabyAchievementAction $action,
    ): BabyAchievementResource {
        $action->handle($babyAchievement, $request->validated());

        return BabyAchievementResource::make($babyAchievement->load('achievement'));
    }

    public function destroy(BabyAchievement $babyAchievement, DeleteBabyAchievement $action): Response
    {
        $action->handle($babyAchievement);

        return response()->noContent();
    }
}
