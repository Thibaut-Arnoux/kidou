<?php

declare(strict_types=1);

namespace App\Actions\Achievement;

use App\Models\Achievement;

final readonly class DeleteAchievement
{
    public function handle(Achievement $achievement): void
    {
        $achievement->delete();
    }
}
