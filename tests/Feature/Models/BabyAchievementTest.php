<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\User;

it('uses uuid as route key', function (): void {
    $baby = Baby::factory()->for(User::factory())->create();
    $achievement = Achievement::factory()->create();
    $baby->achievements()->attach($achievement, ['note' => null]);

    $link = BabyAchievement::query()->first();

    expect($link->getRouteKeyName())->toBe('uuid');
});

it('belongs to an achievement', function (): void {
    $baby = Baby::factory()->for(User::factory())->create();
    $achievement = Achievement::factory()->create();
    $baby->achievements()->attach($achievement, ['note' => null]);

    $link = BabyAchievement::query()->first();

    expect($link->achievement->id)->toBe($achievement->id);
});
