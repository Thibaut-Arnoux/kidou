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
    $this->customCategory = Category::factory()->custom()->create();
});

it('creates a custom achievement', function (): void {
    $this->postJson('/api/v1/achievements', [
        'name' => 'First time at the park',
        'description' => 'Visited the local park',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'First time at the park')
        ->assertJsonPath('data.description', 'Visited the local park')
        ->assertJsonPath('data.link', null);

    $this->assertDatabaseHas('achievements', [
        'name' => 'First time at the park',
        'user_id' => $this->user->id,
        'category_id' => $this->customCategory->id,
    ]);
});

it('creates a custom achievement with optional fields', function (): void {
    $this->postJson('/api/v1/achievements', [
        'name' => 'First smile at grandma',
        'description' => null,
        'expected_age_min_months' => 2,
        'expected_age_max_months' => 6,
    ])
        ->assertCreated()
        ->assertJsonPath('data.expected_age_min_months', 2)
        ->assertJsonPath('data.expected_age_max_months', 6);
});

it('creates a custom achievement with a provided uuid', function (): void {
    $uuid = fake()->uuid();

    $this->postJson('/api/v1/achievements', [
        'uuid' => $uuid,
        'name' => 'Custom with UUID',
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);
});

it('validates required fields for custom achievement creation', function (): void {
    $this->postJson('/api/v1/achievements', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('validates expected_age_max_months is gte expected_age_min_months', function (): void {
    $this->postJson('/api/v1/achievements', [
        'name' => 'Test',
        'expected_age_min_months' => 10,
        'expected_age_max_months' => 5,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expected_age_max_months']);
});

it('deletes a custom achievement', function (): void {
    $achievement = Achievement::factory()
        ->for($this->customCategory)
        ->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}")
        ->assertNoContent();

    $this->assertDatabaseMissing('achievements', ['id' => $achievement->id]);
});

it('cannot delete a predefined achievement', function (): void {
    $category = Category::factory()->create();
    $achievement = Achievement::factory()->for($category)->create(['user_id' => null]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}")
        ->assertForbidden();

    $this->assertDatabaseHas('achievements', ['id' => $achievement->id]);
});

it('cannot delete another user custom achievement', function (): void {
    $otherUser = User::factory()->create();
    $achievement = Achievement::factory()
        ->for($this->customCategory)
        ->create(['user_id' => $otherUser->id]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}")
        ->assertForbidden();
});
