<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\LimitOffsetPaginator;

it('returns a LimitOffsetPaginator instance', function (): void {
    User::factory()->count(3)->create();

    $paginator = User::query()->limitOffsetPaginate();

    expect($paginator)->toBeInstanceOf(LimitOffsetPaginator::class);
});

it('uses the default limit when not provided in the request', function (): void {
    User::factory()->count(5)->create();

    $paginator = User::query()->limitOffsetPaginate(defaultLimit: 3);

    expect($paginator->data)->toHaveCount(3);
    expect($paginator->limit)->toBe(3);
    expect($paginator->offset)->toBe(0);
});

it('reads limit and offset from the request', function (): void {
    User::factory()->count(10)->create();

    request()->replace(['limit' => 4, 'offset' => 3]);

    $paginator = User::query()->limitOffsetPaginate();

    expect($paginator->data)->toHaveCount(4);
    expect($paginator->limit)->toBe(4);
    expect($paginator->offset)->toBe(3);
});

it('returns the total count regardless of the pagination window', function (): void {
    User::factory()->count(10)->create();

    request()->replace(['limit' => 3, 'offset' => 0]);

    $paginator = User::query()->limitOffsetPaginate();

    expect($paginator->total)->toBe(10);
});

it('applies query scopes before paginating', function (): void {
    User::factory()->count(6)->create();
    User::factory()->count(4)->create(['name' => 'Alice']);

    request()->replace(['limit' => 2, 'offset' => 0]);

    $paginator = User::query()->where('name', 'Alice')->limitOffsetPaginate();

    expect($paginator->data)->toHaveCount(2);
    expect($paginator->total)->toBe(4);
});

it('serializes to json with the expected structure', function (): void {
    User::factory()->count(2)->create();

    request()->replace(['limit' => 2, 'offset' => 0]);

    $paginator = User::query()->limitOffsetPaginate();
    $json = json_decode(json_encode($paginator), true);

    expect($json)->toHaveKeys(['data', 'total', 'limit', 'offset']);
    expect($json['total'])->toBe(2);
    expect($json['limit'])->toBe(2);
    expect($json['offset'])->toBe(0);
    expect($json['data'])->toHaveCount(2);
});
