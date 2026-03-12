<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\BabyAchievement;

final readonly class DeleteBabyAchievement
{
    public function handle(BabyAchievement $babyAchievement): void
    {
        $babyAchievement->delete();
    }
}
