<?php

declare(strict_types=1);

namespace App\Actions\MilkGoal;

use App\Models\MilkGoal;

final readonly class DeleteMilkGoal
{
    public function handle(MilkGoal $milkGoal): void
    {
        $milkGoal->delete();
    }
}
