<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Baby::factory()->for($this->user)->create();
    $this->actingAs($this->user);
});

it('lists all achievements', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->count(3)->for($category)->create();

    $this->getJson('/api/v1/achievements')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('returns empty list when no achievements exist', function (): void {
    $this->getJson('/api/v1/achievements')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns achievement fields correctly', function (): void {
    $category = Category::factory()->create();
    $achievement = Achievement::factory()->for($category)->create([
        'name' => 'Head Control',
        'description' => 'Holds head steady.',
        'expected_age_min_months' => 1,
        'expected_age_max_months' => 4,
    ]);

    $this->getJson('/api/v1/achievements')
        ->assertOk()
        ->assertJsonPath('data.0.id', $achievement->uuid)
        ->assertJsonPath('data.0.category_id', $category->uuid)
        ->assertJsonPath('data.0.name', 'Head Control')
        ->assertJsonPath('data.0.description', 'Holds head steady.')
        ->assertJsonPath('data.0.expected_age_min_months', 1)
        ->assertJsonPath('data.0.expected_age_max_months', 4);
});

it('returns achievements ordered by expected_age_min_months ascending', function (): void {
    $category = Category::factory()->create();
    Achievement::factory()->for($category)->create(['expected_age_min_months' => 12]);
    Achievement::factory()->for($category)->create(['expected_age_min_months' => 3]);
    Achievement::factory()->for($category)->create(['expected_age_min_months' => 6]);

    $response = $this->getJson('/api/v1/achievements')->assertOk();

    expect($response->json('data.0.expected_age_min_months'))->toBe(3)
        ->and($response->json('data.1.expected_age_min_months'))->toBe(6)
        ->and($response->json('data.2.expected_age_min_months'))->toBe(12);
});
