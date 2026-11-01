<?php

namespace Sunchayn\Nimbus\Http\Api\Relay;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentIdEnum;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;

class NimbusRelayRequest extends FormRequest
{
    /**
     * @return array<string, string|object|array<array-key, string|object>>
     */
    public function rules(): array
    {
        return [
            'method' => 'required',
            'endpoint' => [
                'required',
                'string',
                fn (string $attribute, mixed $value, Closure $fail) => $this->validateEndpointScheme($value, $fail),
            ],
            'environment' => 'sometimes|nullable|string',
            'authorization' => 'sometimes|array',
            'authorization.type' => [
                'required_with:authorization',
                Rule::in(AuthorizationTypeEnum::cases()),
                Rule::when(
                    condition: ! $this->isCurrentHost(),
                    rules: [
                        Rule::notIn([
                            AuthorizationTypeEnum::CurrentUser->value,
                            AuthorizationTypeEnum::Impersonate->value,
                        ]),
                    ],
                ),
            ],
            'authorization.value' => 'sometimes',
            'body' => 'sometimes',
            'headers' => 'sometimes',
            'headers.*.key' => 'required|string',
            'headers.*.value' => 'required',
        ];
    }

    public function environmentId(): string
    {
        $environment = $this->input('environment');

        return is_string($environment) && $environment !== '' ? $environment : EnvironmentIdEnum::Current->value;
    }

    public function isCurrentHost(): bool
    {
        return $this->environmentId() === EnvironmentIdEnum::Current->value;
    }

    private function validateEndpointScheme(mixed $endpoint, Closure $fail): void
    {
        $scheme = is_string($endpoint) ? parse_url($endpoint, PHP_URL_SCHEME) : null;

        if ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            $fail('The endpoint must use the http or https scheme.');
        }
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
