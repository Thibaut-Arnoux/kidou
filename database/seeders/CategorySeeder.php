<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Motor Skills', 'slug' => 'motor-skills', 'is_custom' => false],
            ['name' => 'Language', 'slug' => 'language', 'is_custom' => false],
            ['name' => 'Social & Emotional', 'slug' => 'social-emotional', 'is_custom' => false],
            ['name' => 'Cognitive', 'slug' => 'cognitive', 'is_custom' => false],
            ['name' => 'Self-Care', 'slug' => 'self-care', 'is_custom' => false],
            ['name' => 'Custom', 'slug' => 'custom', 'is_custom' => true],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
