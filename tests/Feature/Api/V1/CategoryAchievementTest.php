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

it('lists predefined achievements for a non-custom category with link status', function (): void {
    $category = Category::factory()->create();
    $linked = Achievement::factory()->for($category)->create();
    $unlinked = Achievement::factory()->for($category)->create();

    $this->baby->achievements()->attach($linked, [
        'achieved_at' => '2026-02-15 14:30:00',
        'note' => 'During tummy time!',
    ]);

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.link.note', 'During tummy time!')
        ->assertJsonPath('data.1.link', null);
});

it('excludes custom achievements from non-custom categories', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->for($category)->create(); // predefined
    Achievement::factory()->for($category)->create(['user_id' => $this->user->id]); // custom — should be excluded

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lists only current user custom achievements for custom category', function (): void {
    $category = Category::factory()->custom()->create();
    $otherUser = User::factory()->create();

    Achievement::factory()->for($category)->create(['user_id' => $this->user->id]);
    Achievement::factory()->for($category)->create(['user_id' => $otherUser->id]); // should be excluded

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns achievement fields correctly', function (): void {
    $category = Category::factory()->create();
    $achievement = Achievement::factory()->for($category)->create([
        'name' => 'Head Control',
        'description' => 'Holds head steady.',
        'expected_age_min_months' => 1,
        'expected_age_max_months' => 4,
    ]);

    $this->getJson("/api/v1/categories/{$category->uuid}/achievements")
        ->assertOk()
        ->assertJsonPath('data.0.id', $achievement->uuid)
        ->assertJsonPath('data.0.name', 'Head Control')
        ->assertJsonPath('data.0.description', 'Holds head steady.')
        ->assertJsonPath('data.0.expected_age_min_months', 1)
        ->assertJsonPath('data.0.expected_age_max_months', 4)
        ->assertJsonPath('data.0.link', null);
});
