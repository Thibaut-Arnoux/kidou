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
    app()->instance(Baby::class, $this->baby);
});

// --- Index ---

it('returns cursor-paginated list of goals', function (): void {
    MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-15']);

    // Another baby's goal should not appear
    MilkGoal::factory()->create(['date' => '2026-02-14']);

    $this->getJson('/api/v1/milk-goals')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.date', '2026-02-15')
        ->assertJsonStructure([
            'data' => [['id', 'date', 'goal', 'created_at', 'updated_at']],
            'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
        ]);
});

it('returns goals ordered by date descending', function (): void {
    MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-10']);
    MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-15']);
    MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-12']);

    $this->getJson('/api/v1/milk-goals')
        ->assertSuccessful()
        ->assertJsonPath('data.0.date', '2026-02-15')
        ->assertJsonPath('data.1.date', '2026-02-12')
        ->assertJsonPath('data.2.date', '2026-02-10');
});

// --- Store ---

it('creates a new milk goal', function (): void {
    $this->postJson('/api/v1/milk-goals', ['date' => '2026-02-15', 'goal' => 800])
        ->assertCreated()
        ->assertJsonPath('data.date', '2026-02-15')
        ->assertJsonPath('data.goal', 800);

    $this->assertDatabaseHas('milk_goals', [
        'baby_id' => $this->baby->id,
        'date' => '2026-02-15',
        'goal' => 800,
    ]);
});

it('stores a goal with a frontend-provided uuid', function (): void {
    $uuid = (string) Str::uuid();

    $this->postJson('/api/v1/milk-goals', ['uuid' => $uuid, 'date' => '2026-02-15', 'goal' => 800])
        ->assertCreated()
        ->assertJsonPath('data.id', $uuid);

    $this->assertDatabaseHas('milk_goals', ['uuid' => $uuid]);
});

it('auto-generates a uuid when none is provided for goal', function (): void {
    $this->postJson('/api/v1/milk-goals', ['date' => '2026-02-15', 'goal' => 800])
        ->assertCreated()
        ->assertJsonPath('data.id', fn (string $id) => Str::isUuid($id));
});

it('rejects a duplicate uuid when storing goal', function (): void {
    $existing = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-14']);

    $this->postJson('/api/v1/milk-goals', ['uuid' => $existing->uuid, 'date' => '2026-02-15', 'goal' => 800])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['uuid']);
});

it('validates date is required when storing', function (): void {
    $this->postJson('/api/v1/milk-goals', ['goal' => 800])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

it('validates goal is required when storing', function (): void {
    $this->postJson('/api/v1/milk-goals', ['date' => '2026-02-15'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['goal']);
});

it('validates goal must be a positive integer when storing', function (): void {
    $this->postJson('/api/v1/milk-goals', ['date' => '2026-02-15', 'goal' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['goal']);
});

it('validates date must be unique per baby when storing', function (): void {
    MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-15']);

    $this->postJson('/api/v1/milk-goals', ['date' => '2026-02-15', 'goal' => 800])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

// --- Show ---

it('returns a single goal', function (): void {
    $goal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-15', 'goal' => 500]);

    $this->getJson('/api/v1/milk-goals/'.$goal->uuid)
        ->assertSuccessful()
        ->assertJsonPath('data.date', '2026-02-15')
        ->assertJsonPath('data.goal', 500);
});

it('returns 404 for non-existent goal', function (): void {
    $this->getJson('/api/v1/milk-goals/'.Str::uuid())
        ->assertNotFound();
});

// --- Update ---

it('updates goal for a milk goal', function (): void {
    $goal = MilkGoal::factory()->for($this->baby)->create(['goal' => 500]);

    $this->putJson('/api/v1/milk-goals/'.$goal->uuid, ['goal' => 750])
        ->assertSuccessful()
        ->assertJsonPath('data.goal', 750);

    $this->assertDatabaseHas('milk_goals', ['uuid' => $goal->uuid, 'goal' => 750]);
});

it('validates goal is required when updating', function (): void {
    $goal = MilkGoal::factory()->for($this->baby)->create();

    $this->putJson('/api/v1/milk-goals/'.$goal->uuid, [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['goal']);
});

it('validates goal must be a positive integer when updating', function (): void {
    $goal = MilkGoal::factory()->for($this->baby)->create();

    $this->putJson('/api/v1/milk-goals/'.$goal->uuid, ['goal' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['goal']);
});

// --- Destroy ---

it('deletes a goal', function (): void {
    $goal = MilkGoal::factory()->for($this->baby)->create();

    $this->deleteJson('/api/v1/milk-goals/'.$goal->uuid)
        ->assertNoContent();

    $this->assertDatabaseMissing('milk_goals', ['uuid' => $goal->uuid]);
});

it('cascade deletes measures when goal is deleted', function (): void {
    $goal = MilkGoal::factory()->for($this->baby)->create();
    $measure = MilkMeasure::factory()->for($goal, 'milkGoal')->create();

    $this->deleteJson('/api/v1/milk-goals/'.$goal->uuid)
        ->assertNoContent();

    $this->assertDatabaseMissing('milk_goals', ['uuid' => $goal->uuid]);
    $this->assertDatabaseMissing('milk_measures', ['uuid' => $measure->uuid]);
});
