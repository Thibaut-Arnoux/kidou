<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\User;

final readonly class BabyAchievementPolicy
{
    public function update(User $user, BabyAchievement $babyAchievement): bool
    {
        /** @var Baby $baby */
        $baby = app(Baby::class);

        return $baby->id === $babyAchievement->baby_id;
    }

    public function delete(User $user, BabyAchievement $babyAchievement): bool
    {
        /** @var Baby $baby */
        $baby = app(Baby::class);

        return $baby->id === $babyAchievement->baby_id;
    }
}
