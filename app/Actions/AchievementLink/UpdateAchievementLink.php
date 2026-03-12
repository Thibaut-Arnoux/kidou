<?php

declare(strict_types=1);

namespace App\Actions\AchievementLink;

use App\Models\BabyAchievement;

final readonly class UpdateAchievementLink
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BabyAchievement $link, array $data): BabyAchievement
    {
        $link->update($data);

        return $link->refresh();
    }
}
