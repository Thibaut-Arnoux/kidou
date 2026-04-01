<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Achievement> */
final class AchievementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $minAge = fake()->numberBetween(0, 30);

        return [
            'category_id' => Category::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'expected_age_min_months' => $minAge,
            'expected_age_max_months' => fake()->numberBetween($minAge, 36),
        ];
    }
}
