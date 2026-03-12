<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Achievement */
final class AchievementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'category_id' => $this->category->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'expected_age_min_months' => $this->expected_age_min_months,
            'expected_age_max_months' => $this->expected_age_max_months,
        ];
    }
}
