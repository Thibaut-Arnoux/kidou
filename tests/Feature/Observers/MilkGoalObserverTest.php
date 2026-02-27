<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\User;
use Illuminate\Support\Str;

it('auto-generates a uuid when creating a milk goal', function (): void {
    $goal = MilkGoal::factory()
        ->for(Baby::factory()->for(User::factory()))
        ->create();

    expect($goal->uuid)->not->toBeEmpty();
    expect(Str::isUuid($goal->uuid))->toBeTrue();
});

it('does not overwrite a provided uuid when creating a milk goal', function (): void {
    $uuid = (string) Str::uuid();

    $goal = MilkGoal::factory()
        ->for(Baby::factory()->for(User::factory()))
        ->create(['uuid' => $uuid]);

    expect($goal->uuid)->toBe($uuid);
});
