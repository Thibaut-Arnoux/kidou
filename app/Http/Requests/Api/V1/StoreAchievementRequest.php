<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAchievementRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'uuid', 'unique:achievements,uuid'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expected_age_min_months' => ['nullable', 'integer', 'min:0', 'max:36'],
            'expected_age_max_months' => ['nullable', 'integer', 'min:0', 'max:36', 'gte:expected_age_min_months'],
        ];
    }
}
