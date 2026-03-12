<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAchievementLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'uuid', 'unique:baby_achievement,uuid'],
            'achieved_at' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
