<?php

declare(strict_types=1);

namespace App\Actions\AchievementLink;

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use Illuminate\Support\Str;

final readonly class LinkAchievement
{
    public function handle(
        Baby $baby,
        Achievement $achievement,
        string $achievedAt,
        ?string $note = null,
        ?string $uuid = null,
    ): BabyAchievement {
        $baby->achievements()->attach($achievement, [
            'achieved_at' => $achievedAt,
            'note' => $note,
            'uuid' => $uuid ?? (string) Str::uuid(),
        ]);

        /** @var BabyAchievement */
        return BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->firstOrFail();
    }
}
