<?php

declare(strict_types=1);

use App\Models\User;

it('returns healthy status from health endpoint', function (): void {
    $this->getJson('/api/v1/health')
        ->assertSuccessful()
        ->assertJsonStructure(['status', 'timestamp'])
        ->assertJson(['status' => 'healthy']);
});

it('returns 401 json for unauthenticated request to protected route', function (): void {
    $this->getJson('/api/v1/user')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('returns authenticated user from protected route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/user')
        ->assertSuccessful()
        ->assertJsonFragment(['email' => $user->email]);
});

it('returns 404 json for non-existent api route', function (): void {
    $this->getJson('/api/v1/non-existent')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('includes rate limit headers on api responses', function (): void {
    $this->getJson('/api/v1/health')
        ->assertSuccessful()
        ->assertHeader('X-RateLimit-Limit')
        ->assertHeader('X-RateLimit-Remaining');
});
