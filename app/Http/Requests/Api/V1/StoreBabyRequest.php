<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBabyRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        /** @var User $user */
        $user = $this->user();

        $this->merge(['user_id' => $user->id]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:255'],
            'user_id' => [Rule::unique('babies')->where('user_id', $this->integer('user_id'))],
        ];
    }
}
