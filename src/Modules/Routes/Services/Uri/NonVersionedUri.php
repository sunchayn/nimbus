<?php

namespace Sunchayn\Nimbus\Modules\Routes\Services\Uri;

use Sunchayn\Nimbus\Modules\Routes\Services\Uri\Concerns\CleansUriPrefix;

class NonVersionedUri implements UriContract
{
    use CleansUriPrefix;

    public function __construct(
        public string $value,
        public string $routesPrefix,
    ) {}

    public function getVersion(): string
    {
        return 'n/a';
    }

    public function getResource(): string
    {
        $parts = $this->parseUriPartsAfterPrefixIfItExists();

        if (count($parts) > 1) {
            // The last segment is the action verb (view, list, create, etc.).
            // Everything before it is the resource group — join all parent
            // segments so nested route groups form nested folders.
            $resource = array_slice($parts, 0, -1);

            // Route parameters (e.g. {user}) never become folders — stop
            // before the first one so they stay in the route's short URI.
            foreach ($resource as $index => $segment) {
                if (str_starts_with($segment, '{')) {
                    $resource = array_slice($resource, 0, $index);
                    break;
                }
            }

            // If truncation left nothing (e.g. "/{type}/action"), fall back to
            // the first segment so the resource still exists in the URI.
            if ($resource !== []) {
                return implode('/', $resource);
            }

            return $parts[0] ?? '';
        }

        // Single-segment path: the one segment is the resource.
        return $parts[0] ?? '';
    }

    /**
     * @return string[]
     */
    private function parseUriPartsAfterPrefixIfItExists(): array
    {
        if (! filled($this->routesPrefix)) {
            return array_values(
                array_filter(explode('/', $this->value), fn ($part): bool => $part !== ''),
            );
        }

        return $this->parseUriPartsAfterPrefix(
            prefix: $this->routesPrefix,
            uri: $this->value,
        );
    }
}
