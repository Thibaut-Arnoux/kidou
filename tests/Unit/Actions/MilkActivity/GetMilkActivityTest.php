<?php

declare(strict_types=1);

use App\Enums\MilkActivity\Period;

it('throws a ValueError when creating Period from an invalid value', function (): void {
    Period::from('invalid');
})->throws(ValueError::class);
