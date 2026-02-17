<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMilkMeasureRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'min:1'],
            'measured_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z', 'before_or_equal:now'],
        ];
    }
}
