<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BabyAchievement;
use Illuminate\Support\Str;

final class BabyAchievementObserver
{
    public function creating(BabyAchievement $babyAchievement): void
    {
        if (empty($babyAchievement->uuid)) {
            $babyAchievement->uuid = (string) Str::uuid();
        }
    }
}
