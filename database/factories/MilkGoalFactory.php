<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Baby;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MilkGoal>
 */
final class MilkGoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'baby_id' => Baby::factory(),
            'date' => fake()->date(),
            'goal' => fake()->numberBetween(100, 1000),
        ];
    }
}
