<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

// --- Index ---

it('requires authentication to list babies', function (): void {
    $this->getJson('/api/v1/babies')
        ->assertUnauthorized();
});

it('returns list of babies when authenticated', function (): void {
    Baby::factory()->for($this->user)->count(2)->create();

    $this->actingAs($this->user)
        ->getJson('/api/v1/babies')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'nickname', 'created_at', 'updated_at']],
        ]);
});

// --- Store ---

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

// --- Show ---

it('requires authentication to show a baby', function (): void {
    $baby = Baby::factory()->for($this->user)->create();

    $this->getJson('/api/v1/babies/'.$baby->uuid)
        ->assertUnauthorized();
});

it('returns a single baby', function (): void {
    $baby = Baby::factory()->for($this->user)->create(['nickname' => 'Lila']);

    $this->actingAs($this->user)
        ->getJson('/api/v1/babies/'.$baby->uuid)
        ->assertOk()
        ->assertJsonPath('data.nickname', 'Lila')
        ->assertJsonPath('data.id', $baby->uuid);
});

it('returns 404 for non-existent baby', function (): void {
    $this->actingAs($this->user)
        ->getJson('/api/v1/babies/'.fake()->uuid())
        ->assertNotFound();
});

// --- Update ---

it('requires authentication to update a baby', function (): void {
    $baby = Baby::factory()->for($this->user)->create();

    $this->putJson('/api/v1/babies/'.$baby->uuid, ['nickname' => 'Updated'])
        ->assertUnauthorized();
});

it('updates a baby nickname', function (): void {
    $baby = Baby::factory()->for($this->user)->create(['nickname' => 'Old']);

    $this->actingAs($this->user)
        ->putJson('/api/v1/babies/'.$baby->uuid, ['nickname' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.nickname', 'New');

    $this->assertDatabaseHas('babies', ['uuid' => $baby->uuid, 'nickname' => 'New']);
});

it('validates nickname is required when updating', function (): void {
    $baby = Baby::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->putJson('/api/v1/babies/'.$baby->uuid, [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nickname']);
});

// --- Destroy ---

it('requires authentication to delete a baby', function (): void {
    $baby = Baby::factory()->for($this->user)->create();

    $this->deleteJson('/api/v1/babies/'.$baby->uuid)
        ->assertUnauthorized();
});

it('deletes a baby', function (): void {
    $baby = Baby::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->deleteJson('/api/v1/babies/'.$baby->uuid)
        ->assertNoContent();

    $this->assertDatabaseMissing('babies', ['uuid' => $baby->uuid]);
});
