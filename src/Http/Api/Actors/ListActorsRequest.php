<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Actors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListActorsRequest extends FormRequest
{
    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            'guard' => ['sometimes', 'nullable', 'string', Rule::in(array_keys((array) config('auth.guards', [])))],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
