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
            ['name' => 'Motor Skills', 'slug' => 'motor-skills'],
            ['name' => 'Language', 'slug' => 'language'],
            ['name' => 'Social & Emotional', 'slug' => 'social-emotional'],
            ['name' => 'Cognitive', 'slug' => 'cognitive'],
            ['name' => 'Self-Care', 'slug' => 'self-care'],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
