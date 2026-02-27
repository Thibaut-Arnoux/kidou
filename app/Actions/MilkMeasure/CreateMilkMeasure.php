<?php

declare(strict_types=1);

namespace App\Actions\MilkMeasure;

use App\Models\MilkGoal;
use App\Models\MilkMeasure;
use Carbon\CarbonInterface;

final readonly class CreateMilkMeasure
{
    public function handle(MilkGoal $milkGoal, int $value, CarbonInterface $measuredAt, ?string $uuid = null): MilkMeasure
    {
        /** @var MilkMeasure */
        return $milkGoal->measures()->create([
            'value' => $value,
            'measured_at' => $measuredAt,
            ...($uuid !== null ? ['uuid' => $uuid] : []),
        ]);
    }
}
