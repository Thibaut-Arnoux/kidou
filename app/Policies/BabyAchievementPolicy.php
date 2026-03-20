<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\User;

final readonly class BabyAchievementPolicy
{
    // TODO: Restore non-nullable `User $user` once authentication is properly set up.
    // The nullable `?User` allows guest access while auth:sanctum is not enforced on these routes.
    // When restoring auth, also add user ownership checks (e.g. $user->babies()->where('id', $baby->id)->exists()).
    public function update(?User $user, BabyAchievement $babyAchievement): bool
    {
        /** @var Baby $baby */
        $baby = resolve(Baby::class);

        return $baby->id === $babyAchievement->baby_id;
    }

    public function delete(?User $user, BabyAchievement $babyAchievement): bool
    {
        /** @var Baby $baby */
        $baby = resolve(Baby::class);

        return $baby->id === $babyAchievement->baby_id;
    }
}
