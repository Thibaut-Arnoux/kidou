<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Achievement;
use Illuminate\Support\Str;

final class AchievementObserver
{
    public function creating(Achievement $achievement): void
    {
        if (empty($achievement->uuid)) {
            $achievement->uuid = (string) Str::uuid();
        }
    }
}
