<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Achievement\ListAchievements;
use App\Http\Resources\AchievementResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListAchievementController
{
    public function __invoke(Request $request, ListAchievements $action): AnonymousResourceCollection
    {
        $category = null;

        if ($request->has('category')) {
            $request->validate(['category' => ['required', 'uuid']]);
            $category = Category::query()->where('uuid', $request->input('category'))->firstOrFail();
        }

        return AchievementResource::collection($action->handle($category));
    }
}
