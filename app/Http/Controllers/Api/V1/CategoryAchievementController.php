<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CategoryAchievement\ListCategoryAchievements;
use App\Http\Resources\AchievementResource;
use App\Models\Baby;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CategoryAchievementController
{
    public function index(Category $category, Baby $baby, ListCategoryAchievements $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $baby->user;

        return AchievementResource::collection($action->handle($category, $baby, $user));
    }
}
