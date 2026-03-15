<?php

declare(strict_types=1);

use App\Models\Baby;
use App\Models\User;

it('returns 403 when user has no baby profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/categories')
        ->assertForbidden()
        ->assertJsonPath('message', 'No active baby profile');
});

it('allows access when user has a baby profile', function (): void {
    $user = User::factory()->create();
    Baby::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/categories')
        ->assertOk();
});
