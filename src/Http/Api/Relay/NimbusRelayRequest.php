<?php

namespace Sunchayn\Nimbus\Http\Api\Relay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sunchayn\Nimbus\Modules\Relay\Authorization\AuthorizationTypeEnum;

class NimbusRelayRequest extends FormRequest
{
    /**
     * @return array<string, string|object|array<array-key, string|object>>
     */
    public function rules(): array
    {
        return [
            'method' => 'required',
            'endpoint' => 'required',
            'authorization' => 'sometimes|array',
            'authorization.type' => ['required_with:authorization', Rule::in(AuthorizationTypeEnum::cases())],
            'authorization.value' => 'sometimes',
            'body' => 'sometimes',
            'headers' => 'sometimes',
            'headers.*.key' => 'required|string',
            'headers.*.value' => 'required',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getBody(): string|array
    {
        $body = $this->validated('body') && filled($this->validated('body'))
            ? $this->validated('body')
            : [];

        return is_string($body)
            ? json_decode($body, true) ?? $body
            : $body;
    }
}
