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
        return $this->parseUriPartsAfterPrefixIfItExists()[0] ?? '';
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
