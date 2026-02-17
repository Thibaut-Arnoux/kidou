<?php

declare(strict_types=1);

namespace App\Actions\MilkGoal;

use App\Models\MilkGoal;

final readonly class ShowMilkGoal
{
    public function handle(MilkGoal $milkGoal): MilkGoal
    {
        return $milkGoal;
    }
}
