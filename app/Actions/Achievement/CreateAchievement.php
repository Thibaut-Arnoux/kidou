<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\User;

final readonly class CreateAchievement
{
    public function handle(
        User $user,
        string $name,
        ?string $description = null,
        ?int $expectedAgeMinMonths = null,
        ?int $expectedAgeMaxMonths = null,
        ?string $uuid = null,
    ): Achievement {
        $customCategory = Category::query()->where('is_custom', true)->firstOrFail();

        /** @var Achievement */
        return $customCategory->achievements()->create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => $description,
            'expected_age_min_months' => $expectedAgeMinMonths,
            'expected_age_max_months' => $expectedAgeMaxMonths,
            ...($uuid !== null ? ['uuid' => $uuid] : []),
        ]);
    }
}
