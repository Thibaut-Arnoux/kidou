<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use App\Models\User;

it('uses uuid as route key', function (): void {
    $measure = MilkMeasure::factory()
        ->for(MilkGoal::factory()->for(Baby::factory()->for(User::factory())), 'milkGoal')
        ->create();

    expect($measure->getRouteKeyName())->toBe('uuid');
});

it('belongs to a milk goal', function (): void {
    $goal = MilkGoal::factory()->for(Baby::factory()->for(User::factory()))->create();
    $measure = MilkMeasure::factory()->for($goal, 'milkGoal')->create();

    expect($measure->milkGoal->id)->toBe($goal->id);
});

it('eager loads milk goal', function (): void {
    $goal = MilkGoal::factory()->for(Baby::factory()->for(User::factory()))->create();
    MilkMeasure::factory()->for($goal, 'milkGoal')->create();

    $measure = MilkMeasure::query()->first();

    expect($measure->relationLoaded('milkGoal'))->toBeTrue();
});
