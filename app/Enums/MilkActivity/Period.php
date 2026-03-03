<?php

declare(strict_types=1);

namespace App\Enums\MilkActivity;

enum Period: string
{
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function interval(): string
    {
        return match ($this) {
            self::Year => 'month',
            default => 'day',
        };
    }
}
