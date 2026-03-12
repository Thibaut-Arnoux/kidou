<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListAchievements
{
    /**
     * @return Collection<int, Achievement>
     */
    public function handle(?Category $category = null): Collection
    {
        return Achievement::query()
            ->with('category')
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
            ->orderBy('id')
            ->get();
    }
}
