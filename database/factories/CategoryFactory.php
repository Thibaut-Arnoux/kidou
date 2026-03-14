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
        /** @var list<string> $words */
        $words = fake()->unique()->words(2);
        $name = implode(' ', $words);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
        ];
    }
}
