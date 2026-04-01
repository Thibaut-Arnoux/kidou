<?php

declare(strict_types=1);

use App\Models\Category;
use Illuminate\Support\Str;

it('auto-generates a uuid when creating a category', function (): void {
    $category = Category::factory()->create();

    expect($category->uuid)->not->toBeEmpty();
    expect(Str::isUuid($category->uuid))->toBeTrue();
});

it('does not overwrite a provided uuid when creating a category', function (): void {
    $uuid = (string) Str::uuid();

    $category = Category::factory()->create(['uuid' => $uuid]);

    expect($category->uuid)->toBe($uuid);
});
