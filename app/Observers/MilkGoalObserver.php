<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MilkGoal;
use Illuminate\Support\Str;

final class MilkGoalObserver
{
    public function creating(MilkGoal $milkGoal): void
    {
        if (empty($milkGoal->uuid)) {
            $milkGoal->uuid = (string) Str::uuid();
        }
    }
}
