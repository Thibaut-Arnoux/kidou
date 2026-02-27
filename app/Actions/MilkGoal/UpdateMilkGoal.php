<?php

declare(strict_types=1);

namespace App\Actions\MilkGoal;

use App\Models\MilkGoal;

final readonly class UpdateMilkGoal
{
    public function handle(MilkGoal $milkGoal, int $goal): MilkGoal
    {
        $milkGoal->update(['goal' => $goal]);

        return $milkGoal;
    }
}
