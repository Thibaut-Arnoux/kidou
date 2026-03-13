<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Support\Result;
use Illuminate\Support\Str;

final readonly class CreateBabyAchievement
{
    public function handle(Baby $baby, Achievement $achievement, ?string $note = null, ?string $uuid = null): Result
    {
        $alreadyLinked = BabyAchievement::query()
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if ($alreadyLinked) {
            return Result::err('Achievement already linked to this baby.');
        }

        $baby->achievements()->attach($achievement, [
            'uuid' => $uuid ?? (string) Str::uuid(),
            'note' => $note,
        ]);

        /** @var BabyAchievement */
        $babyAchievement = BabyAchievement::query()
            ->with('achievement')
            ->where('baby_id', $baby->id)
            ->where('achievement_id', $achievement->id)
            ->firstOrFail();

        return Result::ok($babyAchievement);
    }
}
