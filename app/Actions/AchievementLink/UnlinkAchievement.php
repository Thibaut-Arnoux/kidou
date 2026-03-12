<?php

declare(strict_types=1);

namespace App\Actions\AchievementLink;

use App\Models\Achievement;
use App\Models\Baby;

final readonly class UnlinkAchievement
{
    public function handle(Baby $baby, Achievement $achievement): void
    {
        $baby->achievements()->detach($achievement);
    }
}
