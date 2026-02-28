<?php

declare(strict_types=1);

namespace App\Actions\MilkActivity;

final readonly class TrendItem
{
    public function __construct(
        public string $date,
        public int $measureValue,
        public int $measureCount,
        public int $goalValue,
    ) {}
}
