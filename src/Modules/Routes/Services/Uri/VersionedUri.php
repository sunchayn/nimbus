<?php

namespace Sunchayn\Nimbus\Modules\Routes\Services\Uri;

use Sunchayn\Nimbus\Modules\Routes\Services\Uri\Concerns\CleansUriPrefix;

class VersionedUri implements UriContract
{
    use CleansUriPrefix;

    /**
     * Clean parts are everything after the route prefix without empty strings.
     *
     * @var string[]
     */
    private array $cleanParts;

    public function __construct(
        public string $value,
        public string $routesPrefix,
    ) {
        $this->cleanParts = $this->parseUriPartsAfterPrefixIfItExists();
    }

    public function getVersion(): string
    {
        if ($this->cleanParts !== [] && $this->isVersionPart($this->cleanParts[0])) {
            return $this->cleanParts[0];
        }

        return 'v1';
    }

    public function getResource(): string
    {
        $parts = $this->cleanParts;

        // If there's a version part, skip it to get the resource.
        if ($parts !== [] && $this->isVersionPart($parts[0])) {
            $parts = array_slice($parts, 1);
        }

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

    private function isVersionPart(string $part): bool
    {
        // Check if the part looks like a version (e.g., v1, v2, 1.0, etc.).
        return preg_match('/^v\d+$/', $part) || preg_match('/^\d+\.\d+$/', $part);
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
