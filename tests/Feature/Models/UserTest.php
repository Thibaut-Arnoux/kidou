<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\User;

it('has many babies', function (): void {
    $user = User::factory()->create();
    $baby = Baby::factory()->for($user)->create();

    expect($user->babies)->toHaveCount(1)
        ->and($user->babies->first()->id)->toBe($baby->id);
});
