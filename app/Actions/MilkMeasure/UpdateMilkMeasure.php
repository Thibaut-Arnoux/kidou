<?php

declare(strict_types=1);

namespace App\Actions\MilkMeasure;

use App\Models\MilkMeasure;

final readonly class UpdateMilkMeasure
{
    public function handle(MilkMeasure $milkMeasure, int $value): MilkMeasure
    {
        $milkMeasure->update(['value' => $value]);

        return $milkMeasure;
    }
}
