<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Achievement\ListAchievements;
use App\Http\Resources\AchievementResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class AchievementController
{
    public function index(ListAchievements $action): AnonymousResourceCollection
    {
        return AchievementResource::collection($action->handle());
    }
}
