<?php

namespace Sunchayn\Nimbus\Modules\Routes\DataTransferObjects;

use Illuminate\Support\Arr;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

class ExtractedRoute
{
    /**
     * @param  string[]  $methods
     * @param  array<string, mixed>  $metadata
     * @param  string[]  $keywords
     */
    public function __construct(
        public readonly Endpoint $uri,
        public readonly array $methods,
        public readonly Schema $schema,
        public readonly array $metadata = [],
        public readonly array $keywords = [],
    ) {}

    /**
     * @return array<string, string>
     */
    public function getRouteSignatures(): array
    {
        return Arr::mapWithKeys(
            $this->methods,
            fn (string $method): array => [$method => strtoupper($method).'@'.$this->getParameterAgnosticUri()],
        );
    }

    /**
     * Normalize a URI template by replacing all parameter placeholders with {}.
     *
     * Examples:
     *   /users/{id} => /users/{}
     *   /users/{user_id} => /users/{}
     *   /posts/{postId}/comments/{commentId} => /posts/{}/comments/{}
     */
    private function getParameterAgnosticUri(): string
    {
        $uri = preg_replace('/\{[^}]+\}/', '{}', $this->uri->value) ?? $this->uri->value;

        return trim($uri, '/');
    }
}
