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

    /**
     * The folder path the endpoint belongs to in the sidebar, one item per level.
     * The last segment is the action and route parameters never become folders.
     *
     * @return non-empty-list<string>
     *
     * @example /users/{user}/posts/list -> ['users']
     * @example /creator/auth/password/reset -> ['creator', 'auth', 'password']
     */
    public function getFolders(): array
    {
        if ($this->resource === self::ROOT_RESOURCE) {
            return [self::ROOT_RESOURCE];
        }

        $segments = explode('/', trim($this->getShortUri(), '/'));

        $folders = array_slice($segments, 0, -1);

        foreach ($folders as $index => $segment) {
            if (str_starts_with($segment, '{')) {
                $folders = array_slice($folders, 0, $index);

                break;
            }
        }

        // A single segment endpoint has no parent folder, so it lives in its resource.
        return $folders === [] ? [$this->resource] : $folders;
    }
}
