<?php

declare(strict_types=1);

namespace App\Actions\MilkMeasure;

use App\Models\MilkMeasure;

final readonly class ShowMilkMeasure
{
    public function handle(MilkMeasure $milkMeasure): MilkMeasure
    {
        return MilkMeasure::query()
            ->withAggregate('milkGoal', 'uuid')
            ->findOrFail($milkMeasure->id);
    }
}
