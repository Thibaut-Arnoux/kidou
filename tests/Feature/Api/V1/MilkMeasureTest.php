<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    $this->goal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-15']);
    app()->instance(Baby::class, $this->baby);
});

// --- Index ---

it('returns list of measures for a goal', function (): void {
    MilkMeasure::factory()->for($this->goal, 'milkGoal')->create(['value' => 120]);

    $this->getJson("/api/v1/milk-goals/{$this->goal->uuid}/measures")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', 120);
});

// --- Store ---

it('creates a measure under a goal', function (): void {
    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'value' => 120,
        'measured_at' => '2026-02-15T10:00:00Z',
    ])
        ->assertCreated()
        ->assertJsonPath('data.value', 120)
        ->assertJsonPath('data.measured_at', '2026-02-15T10:00:00Z');

    $this->assertDatabaseCount('milk_measures', 1);
    $this->assertDatabaseHas('milk_measures', [
        'milk_goal_id' => $this->goal->id,
        'value' => 120,
    ]);
});

it('stores a measure with a frontend-provided uuid', function (): void {
    $uuid = (string) Str::uuid();

    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'uuid' => $uuid,
        'value' => 120,
        'measured_at' => '2026-02-15T10:00:00Z',
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);

    $this->assertDatabaseHas('milk_measures', ['uuid' => $uuid]);
});

it('auto-generates a uuid when none is provided', function (): void {
    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'value' => 120,
        'measured_at' => '2026-02-15T10:00:00Z',
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', fn (string $id) => Str::isUuid($id));
});

it('rejects a duplicate uuid when storing measure', function (): void {
    $existing = MilkMeasure::factory()->for($this->goal, 'milkGoal')->create();

    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'uuid' => $existing->uuid,
        'value' => 120,
        'measured_at' => '2026-02-15T10:00:00Z',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uuid']);
});

it('validates value is required when storing measure', function (): void {
    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'measured_at' => '2026-02-15T10:00:00Z',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['value']);
});

it('validates value must be a positive integer when storing measure', function (): void {
    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'value' => 0,
        'measured_at' => '2026-02-15T10:00:00Z',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['value']);
});

it('validates measured_at is required when storing measure', function (): void {
    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'value' => 120,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['measured_at']);
});

it('validates measured_at must be ISO 8601 UTC format when storing measure', function (): void {
    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'value' => 120,
        'measured_at' => 'not-a-date',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['measured_at']);
});

it('rejects measured_at without Zulu suffix when storing measure', function (): void {
    $this->postJson("/api/v1/milk-goals/{$this->goal->uuid}/measures", [
        'value' => 120,
        'measured_at' => '2026-02-15 10:00:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['measured_at']);
});

// --- Show ---

it('returns a single measure', function (): void {
    $measure = MilkMeasure::factory()->for($this->goal, 'milkGoal')->create(['value' => 150]);

    $this->getJson("/api/v1/milk-goals/{$this->goal->uuid}/measures/{$measure->uuid}")
        ->assertSuccessful()
        ->assertJsonPath('data.value', 150);
});

it('returns 404 for measure belonging to another goal (scoped binding)', function (): void {
    $otherGoal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-16']);
    $measure = MilkMeasure::factory()->for($otherGoal, 'milkGoal')->create();

    $this->getJson("/api/v1/milk-goals/{$this->goal->uuid}/measures/{$measure->uuid}")
        ->assertNotFound();
});

// --- Update ---

it('updates a measure value', function (): void {
    $measure = MilkMeasure::factory()->for($this->goal, 'milkGoal')->create(['value' => 120]);

    $this->putJson("/api/v1/milk-goals/{$this->goal->uuid}/measures/{$measure->uuid}", [
        'value' => 200,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.value', 200);

    $this->assertDatabaseHas('milk_measures', ['uuid' => $measure->uuid, 'value' => 200]);
});

it('validates value is required when updating measure', function (): void {
    $measure = MilkMeasure::factory()->for($this->goal, 'milkGoal')->create();

    $this->putJson("/api/v1/milk-goals/{$this->goal->uuid}/measures/{$measure->uuid}", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['value']);
});

// --- Destroy ---

it('deletes a measure', function (): void {
    $measure = MilkMeasure::factory()->for($this->goal, 'milkGoal')->create();

    $this->deleteJson("/api/v1/milk-goals/{$this->goal->uuid}/measures/{$measure->uuid}")
        ->assertNoContent();

    $this->assertDatabaseMissing('milk_measures', ['uuid' => $measure->uuid]);
    // Goal should still exist
    $this->assertDatabaseHas('milk_goals', ['uuid' => $this->goal->uuid]);
});

it('cannot delete a measure from another goal (scoped binding)', function (): void {
    $otherGoal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-16']);
    $measure = MilkMeasure::factory()->for($otherGoal, 'milkGoal')->create();

    $this->deleteJson("/api/v1/milk-goals/{$this->goal->uuid}/measures/{$measure->uuid}")
        ->assertNotFound();
});
