<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Achievement\CreateAchievement;
use App\Actions\Achievement\DeleteAchievement;
use App\Http\Requests\Api\V1\StoreAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\Baby;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AchievementController
{
    public function store(StoreAchievementRequest $request, Baby $baby, CreateAchievement $action): JsonResponse
    {
        /** @var User $user */
        $user = $baby->user;

        /** @var string $name */
        $name = $request->validated('name');

        $achievement = $action->handle(
            user: $user,
            name: $name,
            description: $request->validated('description'),
            expectedAgeMinMonths: $request->validated('expected_age_min_months'),
            expectedAgeMaxMonths: $request->validated('expected_age_max_months'),
            uuid: $request->validated('uuid'),
        );

        return AchievementResource::make($achievement)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Achievement $achievement, Baby $baby, DeleteAchievement $action): Response
    {
        /** @var User $user */
        $user = $baby->user;

        if ($achievement->user_id === null || $achievement->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $action->handle($achievement);

        return response()->noContent();
    }
}
