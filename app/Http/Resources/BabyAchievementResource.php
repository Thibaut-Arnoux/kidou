<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BabyAchievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BabyAchievement */
final class BabyAchievementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'achievement_id' => $this->achievement->uuid,
            'note' => $this->note,
            'created_at' => $this->created_at->toIso8601ZuluString(),
        ];
    }
}
