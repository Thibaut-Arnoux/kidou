<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Baby;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListCategories
{
    /**
     * @return Collection<int, Category>
     */
    public function handle(Baby $baby, User $user): Collection
    {
        return Category::query()
            ->withCount([
                'achievements as total_achievements' => function ($query) use ($user) {
                    /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Achievement> $query */
                    $query->where(function ($q) use ($user) {
                        $q->where(function ($inner) {
                            $inner->whereRaw('categories.is_custom = false')
                                ->whereNull('achievements.user_id');
                        })->orWhere(function ($inner) use ($user) {
                            $inner->whereRaw('categories.is_custom = true')
                                ->where('achievements.user_id', $user->id);
                        });
                    });
                },
                'achievements as completed_achievements' => function ($query) use ($baby, $user) {
                    /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Achievement> $query */
                    $query->where(function ($q) use ($user) {
                        $q->where(function ($inner) {
                            $inner->whereRaw('categories.is_custom = false')
                                ->whereNull('achievements.user_id');
                        })->orWhere(function ($inner) use ($user) {
                            $inner->whereRaw('categories.is_custom = true')
                                ->where('achievements.user_id', $user->id);
                        });
                    })->whereHas('babies', function ($q) use ($baby) {
                        $q->where('baby_achievement.baby_id', $baby->id);
                    });
                },
            ])
            ->orderBy('id')
            ->get();
    }
}
