<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\User;
use Illuminate\Support\Str;

it('auto-generates a uuid when creating a baby', function (): void {
    $baby = Baby::factory()->for(User::factory())->create();

    expect($baby->uuid)->not->toBeEmpty();
    expect(Str::isUuid($baby->uuid))->toBeTrue();
});

it('does not overwrite a provided uuid when creating a baby', function (): void {
    $uuid = (string) Str::uuid();

    $baby = Baby::factory()->for(User::factory())->create(['uuid' => $uuid]);

    expect($baby->uuid)->toBe($uuid);
});
