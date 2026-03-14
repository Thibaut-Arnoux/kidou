<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListBabyAchievements
{
    /**
     * @return Collection<int, BabyAchievement>
     */
    public function handle(Baby $baby, ?Category $category = null): Collection
    {
        return BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->when($category, fn (Builder $query, Category $category) => $query->whereHas(
                'achievement',
                fn (Builder $q) => $q->where('category_id', $category->id),
            ))
            ->with('achievement')
            ->orderBy('id')
            ->get();
    }
}
