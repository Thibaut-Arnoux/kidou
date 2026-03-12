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
    $this->category = Category::factory()->create();
});

it('links an achievement to a baby', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-02-15T14:30:00',
        'note' => 'During tummy time!',
    ])
        ->assertCreated()
        ->assertJsonPath('data.achieved_at', '2026-02-15T14:30:00Z')
        ->assertJsonPath('data.note', 'During tummy time!');

    $this->assertDatabaseHas('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
        'note' => 'During tummy time!',
    ]);
});

it('links an achievement with auto-generated uuid', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-02-15T14:30:00',
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id']]);
});

it('links an achievement with a provided uuid', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $uuid = fake()->uuid();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'uuid' => $uuid,
        'achieved_at' => '2026-02-15T14:30:00',
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);
});

it('prevents duplicate links for the same baby and achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['achieved_at' => now()]);

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-03-01T10:00:00',
    ])
        ->assertUnprocessable();
});

it('validates required fields for linking', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson("/api/v1/achievements/{$achievement->uuid}/link", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['achieved_at']);
});

it('updates a linked achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, [
        'achieved_at' => '2026-02-15 14:30:00',
        'note' => 'Old note',
    ]);

    $this->putJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-03-01T10:00:00',
        'note' => 'Updated note',
    ])
        ->assertOk()
        ->assertJsonPath('data.note', 'Updated note');

    $this->assertDatabaseHas('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
        'note' => 'Updated note',
    ]);
});

it('returns 404 when updating a non-linked achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->putJson("/api/v1/achievements/{$achievement->uuid}/link", [
        'achieved_at' => '2026-03-01T10:00:00',
    ])
        ->assertNotFound();
});

it('unlinks an achievement from a baby', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['achieved_at' => now()]);

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}/link")
        ->assertNoContent();

    $this->assertDatabaseMissing('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
    ]);
});

it('returns 404 when unlinking a non-linked achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->deleteJson("/api/v1/achievements/{$achievement->uuid}/link")
        ->assertNotFound();
});
