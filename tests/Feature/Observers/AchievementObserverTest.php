<?php

declare(strict_types=1);

use App\Models\Achievement;
use Illuminate\Support\Str;

it('auto-generates a uuid when creating an achievement', function (): void {
    $achievement = Achievement::factory()->create();

    expect($achievement->uuid)->not->toBeEmpty();
    expect(Str::isUuid($achievement->uuid))->toBeTrue();
});

it('does not overwrite a provided uuid when creating an achievement', function (): void {
    $uuid = (string) Str::uuid();

    $achievement = Achievement::factory()->create(['uuid' => $uuid]);

    expect($achievement->uuid)->toBe($uuid);
});
