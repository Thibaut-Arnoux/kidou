<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    $this->actingAs($this->user);
});

it('lists all categories', function (): void {
    $categories = Category::factory()->count(3)->create();

    $this->getJson('/api/v1/achievement-categories')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $categories->first()->uuid)
        ->assertJsonPath('data.0.name', $categories->first()->name)
        ->assertJsonPath('data.0.slug', $categories->first()->slug);
});

it('returns empty list when no categories exist', function (): void {
    $this->getJson('/api/v1/achievement-categories')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
