<?php

declare(strict_types=1);

namespace App\Actions\MilkMeasure;

use App\Models\MilkMeasure;

final readonly class DeleteMilkMeasure
{
    public function handle(MilkMeasure $milkMeasure): void
    {
        $milkMeasure->delete();
    }
}
