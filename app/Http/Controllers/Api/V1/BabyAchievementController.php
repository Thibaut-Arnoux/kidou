<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\BabyAchievement\CreateBabyAchievement;
use App\Actions\BabyAchievement\DeleteBabyAchievement;
use App\Actions\BabyAchievement\ListBabyAchievements;
use App\Actions\BabyAchievement\UpdateBabyAchievement as UpdateBabyAchievementAction;
use App\Http\Requests\Api\V1\StoreBabyAchievementRequest;
use App\Http\Requests\Api\V1\UpdateBabyAchievementRequest;
use App\Http\Resources\BabyAchievementResource;
use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final readonly class BabyAchievementController
{
    public function index(Baby $baby, ListBabyAchievements $action): AnonymousResourceCollection
    {
        return BabyAchievementResource::collection($action->handle($baby));
    }

    public function store(StoreBabyAchievementRequest $request, Achievement $achievement, Baby $baby, CreateBabyAchievement $action): JsonResponse
    {
        /** @var string|null $note */
        $note = $request->validated('note');

        /** @var string|null $uuid */
        $uuid = $request->validated('uuid');

        $result = $action->handle(
            baby: $baby,
            achievement: $achievement,
            note: $note,
            uuid: $uuid,
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
        Gate::authorize('update', $babyAchievement);

        $action->handle($babyAchievement, $request->validated());

        return BabyAchievementResource::make($babyAchievement->load('achievement'));
    }

    public function destroy(BabyAchievement $babyAchievement, DeleteBabyAchievement $action): Response
    {
        Gate::authorize('delete', $babyAchievement);

        $action->handle($babyAchievement);

        return response()->noContent();
    }
}
