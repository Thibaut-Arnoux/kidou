<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAchievementLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'achieved_at' => ['sometimes', 'required', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
