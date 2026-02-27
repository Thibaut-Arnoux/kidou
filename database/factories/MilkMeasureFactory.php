<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MilkGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MilkMeasure>
 */
final class MilkMeasureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'milk_goal_id' => MilkGoal::factory(),
            'value' => fake()->numberBetween(10, 300),
            'measured_at' => fake()->dateTime(),
        ];
    }
}
