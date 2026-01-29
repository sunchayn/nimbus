<?php

namespace Sunchayn\Nimbus\Modules\Routes\ValueObjects;

use RuntimeException;
use Sunchayn\Nimbus\Modules\Routes\Services\Uri\NonVersionedUri;
use Sunchayn\Nimbus\Modules\Routes\Services\Uri\VersionedUri;

readonly class Endpoint
{
    public function __construct(
        public string $version,
        public string $resource,
        public string $value,
        public ?string $shortUriOverride = null,
    ) {}

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

        if ($this->resource === '') {
            throw new RuntimeException('Invalid ValueObject. The resource cannot be empty.');
        }

        $resourcePos = strpos($this->value, '/'.$this->resource);

        if ($resourcePos === false) {
            throw new RuntimeException('Invalid ValueObject. The `resource` MUST exist in the URI.');
        }

        // To get the short URL, we remove everything before the resource.
        // This will include things like the versioning and the API prefix when provided.
        // @example /rest-api/v1/users/{user} -> /users/{user}
        // @example users/{user} -> users/{user}
        return substr($this->value, $resourcePos);
    }
}
