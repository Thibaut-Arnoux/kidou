<?php

declare(strict_types=1);

namespace App\Actions\BabyAchievement;

use App\Models\BabyAchievement;

final readonly class UpdateBabyAchievement
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BabyAchievement $babyAchievement, array $data): BabyAchievement
    {
        $babyAchievement->update($data);

        return $babyAchievement->refresh();
    }
}
