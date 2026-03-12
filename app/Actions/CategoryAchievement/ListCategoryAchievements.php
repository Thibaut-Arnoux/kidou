<?php

declare(strict_types=1);

namespace App\Actions\CategoryAchievement;

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListCategoryAchievements
{
    /**
     * @return Collection<int, Achievement>
     */
    public function handle(Category $category, Baby $baby, User $user): Collection
    {
        $query = $category->achievements();

        if ($category->is_custom) {
            $query->customForUser($user);
        } else {
            $query->predefined();
        }

        $achievements = $query->get();

        $links = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->whereIn('achievement_id', $achievements->pluck('id'))
            ->get()
            ->keyBy('achievement_id');

        return $achievements->each(function (Achievement $achievement) use ($links): void {
            $achievement->setRelation('babyLink', $links->get($achievement->id));
        });
    }
}
