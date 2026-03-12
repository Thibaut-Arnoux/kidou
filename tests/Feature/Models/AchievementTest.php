<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Category;

it('uses uuid as route key', function (): void {
    $achievement = Achievement::factory()->create();

    expect($achievement->getRouteKeyName())->toBe('uuid');
});

it('belongs to a category', function (): void {
    $category = Category::factory()->create();
    $achievement = Achievement::factory()->for($category)->create();

    expect($achievement->category->id)->toBe($category->id);
});
