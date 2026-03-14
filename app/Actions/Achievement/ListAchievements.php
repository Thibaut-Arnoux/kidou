<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListAchievements
{
    /**
     * @return Collection<int, Achievement>
     */
    public function handle(?Category $category = null): Collection
    {
        $query = Achievement::query()
            ->with('category')
            ->orderBy('id');

        if ($category instanceof Category) {
            $query->where('category_id', $category->id);
        }

        return $query->get();
    }
}
