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
        // If there's a version part, skip it to get the resource.
        if ($this->cleanParts !== [] && $this->isVersionPart($this->cleanParts[0])) {
            return $this->cleanParts[1] ?? '';
        }

        // If there's no version part, the first part is the resource.
        return $this->cleanParts[0] ?? '';
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
