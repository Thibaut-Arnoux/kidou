<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('requires authentication to create a baby', function (): void {
    $this->postJson('/api/v1/babies', ['nickname' => 'Lila'])
        ->assertUnauthorized();
});

it('creates a new baby', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/babies', ['nickname' => 'Lila'])
        ->assertCreated()
        ->assertJsonPath('data.nickname', 'Lila');

    $this->assertDatabaseHas('babies', [
        'user_id' => $this->user->id,
        'nickname' => 'Lila',
    ]);
});

it('validates nickname is required when creating', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/babies', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nickname']);
});

it('validates nickname max length when creating', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/v1/babies', ['nickname' => str_repeat('a', 256)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nickname']);
});

it('prevents creating a second baby for the same user', function (): void {
    Baby::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->postJson('/api/v1/babies', ['nickname' => 'Second'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);
});
