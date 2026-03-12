<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AchievementLink\LinkAchievement;
use App\Actions\AchievementLink\UnlinkAchievement;
use App\Actions\AchievementLink\UpdateAchievementLink;
use App\Http\Requests\Api\V1\StoreAchievementLinkRequest;
use App\Http\Requests\Api\V1\UpdateAchievementLinkRequest;
use App\Http\Resources\BabyAchievementResource;
use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AchievementLinkController
{
    public function store(
        StoreAchievementLinkRequest $request,
        Achievement $achievement,
        Baby $baby,
        LinkAchievement $action,
    ): JsonResponse {
        $existing = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if ($existing) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Achievement already linked to this baby.');
        }

        /** @var string $achievedAt */
        $achievedAt = $request->validated('achieved_at');

        $link = $action->handle(
            baby: $baby,
            achievement: $achievement,
            achievedAt: $achievedAt,
            note: $request->validated('note'),
            uuid: $request->validated('uuid'),
        );

        return BabyAchievementResource::make($link)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateAchievementLinkRequest $request,
        Achievement $achievement,
        Baby $baby,
        UpdateAchievementLink $action,
    ): BabyAchievementResource {
        $link = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->firstOrFail();

        $action->handle($link, $request->validated());

        return BabyAchievementResource::make($link);
    }

    public function destroy(
        Achievement $achievement,
        Baby $baby,
        UnlinkAchievement $action,
    ): Response {
        $exists = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if (! $exists) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $action->handle($baby, $achievement);

        return response()->noContent();
    }
}
