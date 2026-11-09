<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Doctor\Services;

class SingleThreadedServerDetector
{
    public const GUIDE_URL = 'https://github.com/sunchayn/nimbus/tree/base/wiki/user-guide#making-nimbus-work-with-single-threaded-servers';

    public function isSameHostAndPort(string $first, string $second): bool
    {
        $firstTarget = $this->hostAndPort($first);

        return $firstTarget !== null && $firstTarget === $this->hostAndPort($second);
    }

    /**
     * The built-in PHP server handles one request at a time, so a relay to itself never ends.
     */
    public function isRunningOnBuiltInServer(): bool
    {
        return PHP_SAPI === 'cli-server';
    }

    /**
     * Suggests a second server on the next port, with the same scheme and host.
     */
    public function suggestBaseUrl(string $url): string
    {
        $parts = parse_url($url) + ['scheme' => 'http', 'host' => '127.0.0.1'];

        return sprintf(
            '%s://%s:%d',
            $parts['scheme'],
            $parts['host'],
            $this->port($parts) + 1,
        );
    }

    private function hostAndPort(string $url): ?string
    {
        $parts = parse_url($url);

        if (! isset($parts['host'])) {
            return null;
        }

        return strtolower($parts['host']).':'.$this->port($parts);
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function port(array $parts): int
    {
        return (int) ($parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80));
    }
}
