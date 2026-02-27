<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\User;

it('uses uuid as route key', function (): void {
    $baby = Baby::factory()->for(User::factory())->create();

    expect($baby->getRouteKeyName())->toBe('uuid');
});
