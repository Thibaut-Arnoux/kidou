<?php

declare(strict_types=1);

namespace App\Actions\MilkGoal;

use App\Models\Baby;
use App\Models\MilkGoal;

final readonly class CreateMilkGoal
{
    public function handle(Baby $baby, string $date, int $goal): MilkGoal
    {
        /** @var MilkGoal */
        return $baby->milkGoals()->create([
            'date' => $date,
            'goal' => $goal,
        ]);
    }
}
