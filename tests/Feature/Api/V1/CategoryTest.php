<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    app()->instance(Baby::class, $this->baby);
});

it('lists all categories with progress meta', function (): void {
    $category = Category::factory()->create();
    $achievements = Achievement::factory()->count(3)->for($category)->create();

    $this->baby->achievements()->attach($achievements->first(), [
        'achieved_at' => now(),
    ]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $category->uuid)
        ->assertJsonPath('data.0.name', $category->name)
        ->assertJsonPath('data.0.slug', $category->slug)
        ->assertJsonPath('data.0.is_custom', false)
        ->assertJsonPath('data.0.total_achievements', 3)
        ->assertJsonPath('data.0.completed_achievements', 1);
});

it('shows zero completed achievements when none are linked', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->count(2)->for($category)->create();

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.total_achievements', 2)
        ->assertJsonPath('data.0.completed_achievements', 0);
});

it('counts only predefined achievements for non-custom categories', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->count(2)->for($category)->create();
    // Custom achievement in a non-custom category should not be counted
    Achievement::factory()->for($category)->create(['user_id' => $this->user->id]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.total_achievements', 2);
});

it('counts only user custom achievements for the custom category', function (): void {
    $category = Category::factory()->custom()->create();
    $otherUser = User::factory()->create();

    Achievement::factory()->count(2)->for($category)->create(['user_id' => $this->user->id]);
    Achievement::factory()->for($category)->create(['user_id' => $otherUser->id]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.total_achievements', 2)
        ->assertJsonPath('data.0.completed_achievements', 0);
});
