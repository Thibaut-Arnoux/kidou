<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\MilkActivity\Period;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MilkActivityRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::enum(Period::class)],
        ];
    }
}
