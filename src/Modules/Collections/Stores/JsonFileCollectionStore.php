<?php

namespace Sunchayn\Nimbus\Modules\Collections\Stores;

use Illuminate\Filesystem\Filesystem;
use Sunchayn\Nimbus\Modules\Collections\Contracts\CollectionStoreContract;
use Sunchayn\Nimbus\Modules\Collections\DataTransferObjects\CollectionData;

/**
 * Stores each collection as a readable JSON file under a configurable directory.
 *
 * The directory is meant to be committed to git, so a whole team shares the
 * same collections through the normal pull/PR flow. One file per collection
 * (named after its id) keeps diffs small and filenames stable across edits.
 */
class JsonFileCollectionStore implements CollectionStoreContract
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $directory,
    ) {}

    public function all(): array
    {
        if (! $this->filesystem->isDirectory($this->directory)) {
            return [];
        }

        $collections = [];

        foreach ($this->filesystem->glob($this->directory.'/*.json') as $index => $path) {
            $decoded = json_decode((string) $this->filesystem->get($path), true);

            if (! is_array($decoded) || ! isset($decoded['id'])) {
                continue;
            }

            $collections[] = CollectionData::fromArray($decoded, $index);
        }

        usort(
            $collections,
            fn (CollectionData $a, CollectionData $b): int => [$a->order, $a->name] <=> [$b->order, $b->name],
        );

        return $collections;
    }

    public function sync(array $collections): void
    {
        $this->filesystem->ensureDirectoryExists($this->directory);

        $keep = [];
        $usedFilenames = [];

        foreach (array_values($collections) as $order => $collection) {
            $filename = $this->filenameFor($collection->id, $usedFilenames);

            // Keyed case-insensitively: on a case-insensitive filesystem
            // (macOS/Windows) put() to "team.json" lands in an existing
            // "Team.json" entry, so prune must not then delete it by exact case.
            $keep[strtolower($filename)] = true;

            $ordered = new CollectionData(
                id: $collection->id,
                name: $collection->name,
                variables: $collection->variables,
                requests: $collection->requests,
                order: $order,
            );

            $this->filesystem->put(
                $this->directory.'/'.$filename,
                json_encode(
                    $ordered->toStorageArray(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ).PHP_EOL,
            );
        }

        $this->pruneExcept($keep);
    }

    /**
     * Derive a safe, traversal-proof, collision-free filename from a collection
     * id. Non-filename characters are stripped; an id that sanitises to nothing
     * still gets persisted under a hashed name; and two ids that would collide
     * (including case-insensitively, for macOS/Windows) are disambiguated with a
     * short hash of the raw id, so a collection is never silently overwritten or
     * dropped.
     *
     * @param  array<string, true>  $usedFilenames  lowercased basenames already taken this sync
     */
    private function filenameFor(string $id, array &$usedFilenames): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $id) ?? '';

        if ($safe === '') {
            $safe = 'collection';
        }

        $candidate = $safe;

        while (isset($usedFilenames[strtolower($candidate)])) {
            $candidate = $safe.'-'.substr(sha1($id), 0, 8);

            // Guard the vanishingly unlikely second collision on the same hash.
            if (isset($usedFilenames[strtolower($candidate)])) {
                $candidate .= '-'.count($usedFilenames);
            }
        }

        $usedFilenames[strtolower($candidate)] = true;

        return $candidate.'.json';
    }

    /**
     * Remove stored collection files no longer present in the kept set. Files
     * that do not decode to a collection (malformed, or foreign) are left alone
     * so a hand-authored file with a syntax error is never destroyed by a sync.
     *
     * @param  array<string, true>  $keep
     */
    private function pruneExcept(array $keep): void
    {
        foreach ($this->filesystem->glob($this->directory.'/*.json') as $path) {
            if (isset($keep[strtolower(basename($path))])) {
                continue;
            }

            $decoded = json_decode((string) $this->filesystem->get($path), true);

            if (! is_array($decoded) || ! isset($decoded['id'])) {
                continue;
            }

            $this->filesystem->delete($path);
        }
    }
}
