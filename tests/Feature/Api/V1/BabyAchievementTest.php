<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\Category;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    $this->category = Category::factory()->create();
    $this->actingAs($this->user);
});

// --- Index ---

it('lists all baby achievements', function (): void {
    $achievements = Achievement::factory()->count(2)->for($this->category)->create();

    foreach ($achievements as $achievement) {
        $this->baby->achievements()->attach($achievement, ['note' => 'test']);
    }

    $this->getJson('/api/v1/baby-achievements')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters baby achievements by category', function (): void {
    $category2 = Category::factory()->create();
    $a1 = Achievement::factory()->for($this->category)->create();
    $a2 = Achievement::factory()->for($category2)->create();

    $this->baby->achievements()->attach($a1, ['note' => null]);
    $this->baby->achievements()->attach($a2, ['note' => null]);

    $this->getJson('/api/v1/baby-achievements?category='.$this->category->uuid)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns baby achievement fields correctly', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => 'Great job!']);

    $link = BabyAchievement::query()->first();

    $this->getJson('/api/v1/baby-achievements')
        ->assertOk()
        ->assertJsonPath('data.0.id', $link->uuid)
        ->assertJsonPath('data.0.achievement_id', $achievement->uuid)
        ->assertJsonPath('data.0.note', 'Great job!')
        ->assertJsonPath('data.0.created_at', $link->created_at->toIso8601ZuluString());
});

// --- Store ---

it('creates a baby achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid, [
        'note' => 'During tummy time!',
    ])
        ->assertCreated()
        ->assertJsonPath('data.achievement_id', $achievement->uuid)
        ->assertJsonPath('data.note', 'During tummy time!');

    $this->assertDatabaseHas('baby_achievement', [
        'baby_id' => $this->baby->id,
        'achievement_id' => $achievement->id,
        'note' => 'During tummy time!',
    ]);
});

it('creates a baby achievement without note', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid)
        ->assertCreated()
        ->assertJsonPath('data.note', null);
});

it('creates a baby achievement with provided uuid', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $uuid = fake()->uuid();

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid, [
        'uuid' => $uuid,
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);
});

it('prevents duplicate baby achievements for the same achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => null]);

    $this->postJson('/api/v1/baby-achievements/'.$achievement->uuid)
        ->assertUnprocessable();
});

it('returns 404 when storing with non-existent achievement', function (): void {
    $this->postJson('/api/v1/baby-achievements/'.fake()->uuid())
        ->assertNotFound();
});

// --- Update ---

it('updates a baby achievement note', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => 'Old note']);
    $link = BabyAchievement::query()->first();

    $this->putJson('/api/v1/baby-achievements/'.$link->uuid, [
        'note' => 'Updated note',
    ])
        ->assertOk()
        ->assertJsonPath('data.note', 'Updated note');

    $this->assertDatabaseHas('baby_achievement', [
        'id' => $link->id,
        'note' => 'Updated note',
    ]);
});

it('clears a baby achievement note', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => 'Has note']);
    $link = BabyAchievement::query()->first();

    $this->putJson('/api/v1/baby-achievements/'.$link->uuid, [
        'note' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.note', null);
});

// --- Destroy ---

it('deletes a baby achievement', function (): void {
    $achievement = Achievement::factory()->for($this->category)->create();
    $this->baby->achievements()->attach($achievement, ['note' => null]);
    $link = BabyAchievement::query()->first();

    $this->deleteJson('/api/v1/baby-achievements/'.$link->uuid)
        ->assertNoContent();

    $this->assertDatabaseMissing('baby_achievement', ['id' => $link->id]);
});

it('returns 404 when deleting non-existent baby achievement', function (): void {
    $this->deleteJson('/api/v1/baby-achievements/'.fake()->uuid())
        ->assertNotFound();
});
