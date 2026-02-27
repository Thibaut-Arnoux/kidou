<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MilkMeasure;
use Illuminate\Support\Str;

final class MilkMeasureObserver
{
    public function creating(MilkMeasure $milkMeasure): void
    {
        if (empty($milkMeasure->uuid)) {
            $milkMeasure->uuid = (string) Str::uuid();
        }
    }
}
