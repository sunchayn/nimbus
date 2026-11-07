<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Magic;

use Illuminate\Foundation\Http\FormRequest;

class SignedUrlRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string'],
            'url' => ['required', 'url'],
        ];
    }
}
