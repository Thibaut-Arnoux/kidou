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
            'achieved_at' => $this->achieved_at->toIso8601ZuluString(),
            'note' => $this->note,
        ];
    }
}
