<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use App\Models\User;

it('uses uuid as route key', function (): void {
    $goal = MilkGoal::factory()->for(Baby::factory()->for(User::factory()))->create();

    expect($goal->getRouteKeyName())->toBe('uuid');
});

it('belongs to a baby', function (): void {
    $baby = Baby::factory()->for(User::factory())->create();
    $goal = MilkGoal::factory()->for($baby)->create();

    expect($goal->baby->id)->toBe($baby->id);
});

it('has many measures', function (): void {
    $goal = MilkGoal::factory()->for(Baby::factory()->for(User::factory()))->create();
    MilkMeasure::factory()->for($goal, 'milkGoal')->create();

    expect($goal->measures)->toHaveCount(1);
});
