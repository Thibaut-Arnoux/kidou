<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Category;

it('uses uuid as route key', function (): void {
    $category = Category::factory()->create();

    expect($category->getRouteKeyName())->toBe('uuid');
});

it('has many achievements', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->for($category)->create();

    expect($category->achievements)->toHaveCount(1);
});
