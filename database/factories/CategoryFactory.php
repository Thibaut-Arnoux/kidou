<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
final class CategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_custom' => false,
        ];
    }

    public function custom(): self
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Custom',
            'slug' => 'custom',
            'is_custom' => true,
        ]);
    }
}
