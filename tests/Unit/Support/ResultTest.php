<?php

declare(strict_types=1);

use App\Support\Result;

it('creates a successful result', function (): void {
    $result = Result::ok('value');

    expect($result->isOk())->toBeTrue();
    expect($result->isErr())->toBeFalse();
    expect($result->value())->toBe('value');
});

it('creates a successful result with no value', function (): void {
    $result = Result::ok();

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBeNull();
});

it('creates a failed result', function (): void {
    $result = Result::err('something went wrong');

    expect($result->isErr())->toBeTrue();
    expect($result->isOk())->toBeFalse();
    expect($result->error())->toBe('something went wrong');
});
