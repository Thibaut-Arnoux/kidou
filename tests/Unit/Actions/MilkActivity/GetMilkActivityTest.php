<?php

declare(strict_types=1);

use App\Actions\MilkActivity\GetMilkActivity;
use App\Models\Baby;

it('throws an exception for an invalid period', function (): void {
    $baby = Baby::factory()->create();

    $action = resolve(GetMilkActivity::class);

    $action->handle($baby, 'invalid');
})->throws(InvalidArgumentException::class, 'Invalid period: invalid');
