<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Baby;
use App\Models\BabyAchievement;
use App\Models\User;
use Illuminate\Support\Str;

it('auto-generates a uuid when attaching a baby achievement', function (): void {
    $baby = Baby::factory()->for(User::factory())->create();
    $achievement = Achievement::factory()->create();

    $baby->achievements()->attach($achievement, ['note' => null]);

    $link = BabyAchievement::query()->first();

    expect($link->uuid)->not->toBeEmpty();
    expect(Str::isUuid($link->uuid))->toBeTrue();
});

it('does not overwrite a provided uuid when attaching a baby achievement', function (): void {
    $baby = Baby::factory()->for(User::factory())->create();
    $achievement = Achievement::factory()->create();
    $uuid = (string) Str::uuid();

    $baby->achievements()->attach($achievement, ['uuid' => $uuid, 'note' => null]);

    $link = BabyAchievement::query()->first();

    expect($link->uuid)->toBe($uuid);
});
