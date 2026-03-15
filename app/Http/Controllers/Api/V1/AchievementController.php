<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Achievement\ListAchievements;
use App\Http\Requests\Api\V1\ListAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class AchievementController
{
    public function index(ListAchievementRequest $request, ListAchievements $action): AnonymousResourceCollection
    {
        $category = $request->validated('category')
            ? Category::query()->where('uuid', $request->validated('category'))->first()
            : null;

        return AchievementResource::collection($action->handle($category));
    }
}
