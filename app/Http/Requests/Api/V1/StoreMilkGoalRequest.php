<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Baby;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMilkGoalRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var Baby $baby */
        $baby = resolve(Baby::class);

        return [
            'uuid' => ['sometimes', 'uuid', 'unique:milk_goals,uuid'],
            'date' => ['required', 'date_format:Y-m-d', Rule::unique('milk_goals')->where('baby_id', $baby->id)],
            'goal' => ['required', 'integer', 'min:1'],
        ];
    }
}
