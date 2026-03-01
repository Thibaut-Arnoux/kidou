<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use App\Models\User;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->baby = Baby::factory()->for($this->user)->create();
    app()->instance(Baby::class, $this->baby);
});

// --- Validation ---

it('rejects request without period', function (): void {
    $this->getJson('/api/v1/milk-activity')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['period']);
});

it('rejects invalid period value', function (): void {
    $this->getJson('/api/v1/milk-activity?period=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['period']);
});

it('rejects day as period', function (): void {
    $this->getJson('/api/v1/milk-activity?period=day')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['period']);
});

// --- Response structure ---

it('returns consistent envelope for all periods', function (string $period): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $this->getJson('/api/v1/milk-activity?period='.$period)
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [['date', 'measure_value', 'measure_count', 'goal_value']],
            'meta' => ['measure_total', 'measure_total_count', 'measure_average', 'goal_count', 'goal_reached_count', 'goal_reached_rate'],
        ]);
})->with(['week', 'month', 'year']);

// --- Week period ---

it('returns aggregated daily data for week period', function (): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $goal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-20', 'goal' => 200]);

    MilkMeasure::factory()->for($goal, 'milkGoal')->create([
        'value' => 100,
        'measured_at' => '2026-02-20 08:00:00',
    ]);

    MilkMeasure::factory()->for($goal, 'milkGoal')->create([
        'value' => 50,
        'measured_at' => '2026-02-20 14:00:00',
    ]);

    $response = $this->getJson('/api/v1/milk-activity?period=week')
        ->assertSuccessful();

    $data = $response->json('data');
    $feb20 = collect($data)->firstWhere('date', '2026-02-20');

    expect($feb20)->not->toBeNull();
    expect($feb20['measure_value'])->toBe(150);
    expect($feb20['measure_count'])->toBe(2);
    expect($feb20['goal_value'])->toBe(200);

    $response->assertJsonPath('meta.measure_total', 150);
    $response->assertJsonPath('meta.measure_total_count', 2);
    $response->assertJsonPath('meta.measure_average', 75);
});

// --- Month period ---

it('returns aggregated daily data for month period', function (): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $goal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-01', 'goal' => 300]);

    MilkMeasure::factory()->for($goal, 'milkGoal')->create([
        'value' => 200,
        'measured_at' => '2026-02-01 09:00:00',
    ]);

    $response = $this->getJson('/api/v1/milk-activity?period=month')
        ->assertSuccessful();

    $data = $response->json('data');
    $feb01 = collect($data)->firstWhere('date', '2026-02-01');

    expect($feb01)->not->toBeNull();
    expect($feb01['measure_value'])->toBe(200);
});

// --- Year period ---

it('returns aggregated daily data for year period', function (): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $goal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-01-15', 'goal' => 500]);

    MilkMeasure::factory()->for($goal, 'milkGoal')->create([
        'value' => 300,
        'measured_at' => '2026-01-15 10:00:00',
    ]);

    MilkMeasure::factory()->for($goal, 'milkGoal')->create([
        'value' => 150,
        'measured_at' => '2026-01-20 10:00:00',
    ]);

    $response = $this->getJson('/api/v1/milk-activity?period=year')
        ->assertSuccessful();

    $data = $response->json('data');
    $jan15 = collect($data)->firstWhere('date', '2026-01-15');

    expect($jan15)->not->toBeNull();
    expect($jan15['measure_value'])->toBe(300);
    expect($jan15['measure_count'])->toBe(1);
    expect($jan15['goal_value'])->toBe(500);

    $response->assertJsonPath('meta.measure_total', 450);
    $response->assertJsonPath('meta.measure_total_count', 2);
    $response->assertJsonPath('meta.measure_average', 225);
});

// --- Empty data ---

it('returns zero summary when no measures exist', function (): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $response = $this->getJson('/api/v1/milk-activity?period=week')
        ->assertSuccessful();

    $data = $response->json('data');

    expect($data)->not->toBeEmpty();
    expect(collect($data)->every(fn (array $item): bool => $item['measure_value'] === 0))->toBeTrue();

    $response->assertJsonPath('meta.measure_total', 0);
    $response->assertJsonPath('meta.measure_total_count', 0);
    $response->assertJsonPath('meta.measure_average', 0);
    $response->assertJsonPath('meta.goal_count', 0);
    $response->assertJsonPath('meta.goal_reached_count', 0);
    $response->assertJsonPath('meta.goal_reached_rate', 0);
});

// --- Goal tracking ---

it('reports goal reached when measure total meets goal', function (): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $goal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-20', 'goal' => 100]);

    MilkMeasure::factory()->for($goal, 'milkGoal')->create([
        'value' => 120,
        'measured_at' => '2026-02-20 10:00:00',
    ]);

    $response = $this->getJson('/api/v1/milk-activity?period=week')
        ->assertSuccessful();

    $response->assertJsonPath('meta.goal_count', 1);
    $response->assertJsonPath('meta.goal_reached_count', 1);
    $response->assertJsonPath('meta.goal_reached_rate', 1);
});

it('reports goal not reached when measure total is below goal', function (): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $goal = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-20', 'goal' => 200]);

    MilkMeasure::factory()->for($goal, 'milkGoal')->create([
        'value' => 50,
        'measured_at' => '2026-02-20 10:00:00',
    ]);

    $response = $this->getJson('/api/v1/milk-activity?period=week')
        ->assertSuccessful();

    $response->assertJsonPath('meta.goal_count', 1);
    $response->assertJsonPath('meta.goal_reached_count', 0);
    $response->assertJsonPath('meta.goal_reached_rate', 0);
});

it('computes correct goal reached rate with mixed results', function (): void {
    Date::setTestNow('2026-02-24 12:00:00');

    $goalReached = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-20', 'goal' => 100]);
    MilkMeasure::factory()->for($goalReached, 'milkGoal')->create([
        'value' => 150,
        'measured_at' => '2026-02-20 10:00:00',
    ]);

    $goalNotReached = MilkGoal::factory()->for($this->baby)->create(['date' => '2026-02-21', 'goal' => 200]);
    MilkMeasure::factory()->for($goalNotReached, 'milkGoal')->create([
        'value' => 50,
        'measured_at' => '2026-02-21 10:00:00',
    ]);

    $response = $this->getJson('/api/v1/milk-activity?period=week')
        ->assertSuccessful();

    $response->assertJsonPath('meta.goal_count', 2);
    $response->assertJsonPath('meta.goal_reached_count', 1);
    $response->assertJsonPath('meta.goal_reached_rate', 0.5);
});
