<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps the latest sent requests in `storage/nimbus/mcp-sent-requests`, so a request id can be looked up later.
 */
class SentRequestStore
{
    public const LIMIT = 50;

    /**
     * @param  array<string, mixed>  $sentRequest
     */
    public function put(array $sentRequest): string
    {
        $id = bin2hex(random_bytes(6));

        try {
            $this->saveSentRequest(id: $id, sentRequest: $sentRequest);
        } catch (Throwable $throwable) {
            Log::warning('Nimbus could not store the MCP sent request.', ['reason' => $throwable->getMessage()]);
        }

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $filepath = $this->getSentRequestFilepath(id: basename($id));

        if (! is_file($filepath)) {
            return null;
        }

        $sentRequest = json_decode((string) file_get_contents($filepath), true);

        return is_array($sentRequest) ? $sentRequest : null;
    }

    /**
     * @param  array<string, mixed>  $sentRequest
     */
    private function saveSentRequest(string $id, array $sentRequest): void
    {
        $dirpath = $this->getSentRequestsDirpath();

        File::ensureDirectoryExists($dirpath);

        $content = (string) json_encode(['id' => $id, ...$sentRequest], JSON_UNESCAPED_SLASHES);

        file_put_contents($this->getSentRequestFilepath(id: $id), $content);

        $this->pruneOldSentRequests(dirpath: $dirpath);
    }

    private function getSentRequestsDirpath(): string
    {
        return storage_path('nimbus/mcp-sent-requests');
    }

    private function getSentRequestFilepath(string $id): string
    {
        return "{$this->getSentRequestsDirpath()}/{$id}.json";
    }

    private function pruneOldSentRequests(string $dirpath): void
    {
        collect(File::glob("{$dirpath}/*.json"))
            ->sortByDesc(static fn (string $filepath): int|false => filemtime($filepath))
            ->slice(self::LIMIT)
            ->each(static fn (string $filepath): bool => @unlink($filepath));
    }
}
