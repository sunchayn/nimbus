<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights;

use Throwable;

/**
 * Hands one insight file from the target request to the relay.
 *
 * The target request writes the file before its response is sent, and the relay reads and deletes it once.
 */
class InsightStore
{
    public const HEADER = 'X-Nimbus-Insight';

    private const DIRECTORY = 'vendor/sunchayn/nimbus/storage/insight';

    // Files the relay never read, for example after a failed relay, are removed after this many seconds.
    private const STALE_AFTER_SECONDS = 600;

    public static function isValidId(mixed $id): bool
    {
        return is_string($id) && preg_match('/^[A-Za-z0-9-]{8,64}$/D', $id) === 1;
    }

    public function write(string $id, string $json): bool
    {
        if (! self::isValidId($id)) {
            return false;
        }

        $directory = base_path(self::DIRECTORY);

        try {
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                return false;
            }

            $this->pruneStaleFiles($directory);

            $temporary = sprintf('%s/%s.tmp', $directory, $id);

            if (file_put_contents($temporary, $json) === false) {
                return false;
            }

            return rename($temporary, $this->path($id));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pull(string $id): ?array
    {
        if (! self::isValidId($id) || ! is_file($this->path($id))) {
            return null;
        }

        $contents = file_get_contents($this->path($id));

        @unlink($this->path($id));

        $decoded = $contents === false
            ? null
            : json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function path(string $id): string
    {
        return base_path(self::DIRECTORY).'/'.$id.'.json';
    }

    private function pruneStaleFiles(string $directory): void
    {
        foreach (glob($directory.'/*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - self::STALE_AFTER_SECONDS) {
                @unlink($file);
            }
        }
    }
}
