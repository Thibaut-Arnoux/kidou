<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use App\Models\User;
use Illuminate\Support\Str;

it('auto-generates a uuid when creating a milk measure', function (): void {
    $measure = MilkMeasure::factory()
        ->for(MilkGoal::factory()->for(Baby::factory()->for(User::factory())), 'milkGoal')
        ->create();

    expect($measure->uuid)->not->toBeEmpty();
    expect(Str::isUuid($measure->uuid))->toBeTrue();
});

it('does not overwrite a provided uuid when creating a milk measure', function (): void {
    $uuid = (string) Str::uuid();

    $measure = MilkMeasure::factory()
        ->for(MilkGoal::factory()->for(Baby::factory()->for(User::factory())), 'milkGoal')
        ->create(['uuid' => $uuid]);

    expect($measure->uuid)->toBe($uuid);
});
