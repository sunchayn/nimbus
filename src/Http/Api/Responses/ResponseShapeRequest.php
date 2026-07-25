<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Responses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class ResponseShapeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string'],
            'endpoint' => ['required', 'string'],
            'response_body' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseBody(): ?array
    {
        $raw = $this->validated('response_body');

        return is_string($raw) ? (@json_decode($raw, true) ?: null) : null;
    }

    public function getTargetRoute(): ?Route
    {
        $method = $this->validated('method');
        $endpoint = (string) $this->validated('endpoint');

        // We preserve full URLs as-is so domain and host route matching constraints remain intact,
        // falling back to path extraction for relative endpoint URIs.
        $hasScheme = preg_match('#^https?://#i', $endpoint);
        $uri = $hasScheme
            ? $endpoint
            : (parse_url($endpoint, PHP_URL_PATH) ?: $endpoint);

        $dummyRequest = Request::create($uri, strtoupper((string) $method));

        try {
            return app('router')->getRoutes()->match($dummyRequest);
        } catch (\Throwable) {
            return null;
        }
    }
}
