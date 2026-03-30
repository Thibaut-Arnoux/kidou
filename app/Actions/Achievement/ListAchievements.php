<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListAchievements
{
    /**
     * @return Collection<int, Achievement>
     */
    public function handle(): Collection
    {
        return Achievement::query()
            ->with('category')
            ->orderBy('expected_age_min_months')
            ->get();
    }
}
