<?php

namespace Sunchayn\Nimbus\Modules\Routes\ValueObjects;

use Illuminate\Support\Str;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Routes\Services\Uri\NonVersionedUri;
use Sunchayn\Nimbus\Modules\Routes\Services\Uri\VersionedUri;

readonly class Endpoint
{
    public const ROOT_RESOURCE = '<root>';

    public string $resource;

    public function __construct(
        public string $version,
        string $resource,
        public string $value,
        public ?string $shortUriOverride = null,
    ) {
        $this->resource = blank($resource)
            ? self::ROOT_RESOURCE
            : $resource;
    }

    public static function fromRaw(string $uri, string $routesPrefix, bool $isVersioned, ?string $shortUriOverride = null): self
    {
        $uriObject = $isVersioned
            ? new VersionedUri($uri, routesPrefix: $routesPrefix)
            : new NonVersionedUri($uri, routesPrefix: $routesPrefix);

        return new self(
            version: $uriObject->getVersion(),
            resource: $uriObject->getResource(),
            value: $uri,
            shortUriOverride: $shortUriOverride,
        );
    }

    public function getShortUri(): string
    {
        if ($this->shortUriOverride !== null) {
            return $this->shortUriOverride;
        }

        if ($this->resource === self::ROOT_RESOURCE) {
            return '/';
        }

        // We try to find the resource segment by checking if it is the beginning of a string,
        // or looking for it with a leading slash, which is common for prefixed or versioned routes.
        $resourcePos = str_starts_with($this->value, $this->resource)
            ? 0
            : strpos($this->value, '/'.$this->resource);

        if ($resourcePos === false) {
            throw new RuntimeException('Invalid ValueObject. The `resource` MUST exist in the URI.');
        }

        // To get the short URL, we remove everything before the resource.
        // This will include things like the versioning and the API prefix when provided.
        // @example /rest-api/v1/users/{user} -> /users/{user}
        // @example users/{user} -> /users/{user}
        // @example / -> /
        return Str::start(substr($this->value, $resourcePos), '/');
    }
}
