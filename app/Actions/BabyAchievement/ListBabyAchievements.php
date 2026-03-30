<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\Baby;
use App\Models\BabyAchievement;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListBabyAchievements
{
    /**
     * @return Collection<int, BabyAchievement>
     */
    public function handle(Baby $baby): Collection
    {
        return BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->with('achievement')
            ->orderBy('id')
            ->get();
    }
}
