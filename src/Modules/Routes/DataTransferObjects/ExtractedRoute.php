<?php

namespace Sunchayn\Nimbus\Modules\Routes\DataTransferObjects;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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

    public function name(): ?string
    {
        $name = $this->metadata['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * The method of a route that has a single method. Use `splitByMethod` first for any other route.
     */
    public function method(): string
    {
        return strtoupper($this->methods[0]);
    }

    /**
     * The id of a route that has a single method, such as `POST api/users`.
     */
    public function id(): string
    {
        return "{$this->method()} ".trim($this->uri->value, '/');
    }

    /**
     * Matches the id, the name, or the URI. The id ignores letter case.
     */
    public function isReferencedBy(string $reference): bool
    {
        if (strcasecmp($this->id(), $reference) === 0) {
            return true;
        }

        if ($this->name() === $reference) {
            return true;
        }

        return trim($this->uri->value, '/') === trim($reference, '/');
    }

    /**
     * One route per method. HEAD is implied by GET, so it only stays when it is the single method.
     *
     * @return array<int, self>
     */
    public function splitByMethod(): array
    {
        $methods = collect($this->methods)
            ->reject(static fn (string $method): bool => strtoupper($method) === 'HEAD')
            ->whenEmpty(fn (): Collection => collect($this->methods));

        return $methods
            ->map(fn (string $method): self => new self(
                uri: $this->uri,
                methods: [$method],
                schema: $this->schema,
                metadata: $this->metadata,
                keywords: $this->keywords,
            ))
            ->values()
            ->all();
    }

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
