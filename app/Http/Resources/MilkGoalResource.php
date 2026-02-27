<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MilkGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MilkGoal */
final class MilkGoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'date' => $this->date->format('Y-m-d'),
            'goal' => $this->goal,
            'created_at' => $this->created_at->toIso8601ZuluString(),
            'updated_at' => $this->updated_at->toIso8601ZuluString(),
        ];
    }
}
