<?php

namespace Sunchayn\Nimbus\Modules\Routes\Services\Uri\Concerns;

trait CleansUriPrefix
{
    /**
     * @return string[]
     */
    protected function parseUriPartsAfterPrefix(string $prefix, string $uri): array
    {
        $parts = explode('/', $uri);

        $cleanParts = array_values(array_filter($parts, fn ($part): bool => $part !== ''));

        // Convert the prefix string (e.g. "app/api" or "api") into an array of parts: ["app", "api"] or ["api"].
        $prefixParts = explode('/', trim($prefix, '/'));

        // Iterate through each prefix part and remove matching items from the start of $parts.
        foreach ($prefixParts as $index => $prefixPart) {
            // Stop checking once we hit a mismatch.
            if (! isset($cleanParts[$index]) || $cleanParts[$index] !== $prefixPart) {
                break;
            }

            unset($cleanParts[$index]);
        }

        return array_values($cleanParts);
    }
}
