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
        private readonly Filesystem $files,
        private readonly string $directory,
    ) {}

    public function all(): array
    {
        if (! $this->files->isDirectory($this->directory)) {
            return [];
        }

        $collections = [];

        foreach ($this->files->glob($this->directory.'/*.json') as $index => $path) {
            $decoded = json_decode((string) $this->files->get($path), true);

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
        $this->files->ensureDirectoryExists($this->directory);

        $keep = [];

        foreach (array_values($collections) as $order => $collection) {
            $filename = $this->filenameFor($collection->id);

            if ($filename === null) {
                continue;
            }

            $keep[$filename] = true;

            $ordered = new CollectionData(
                id: $collection->id,
                name: $collection->name,
                variables: $collection->variables,
                requests: $collection->requests,
                order: $order,
            );

            $this->files->put(
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
     * Derive a safe, traversal-proof filename from a collection id.
     */
    private function filenameFor(string $id): ?string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $id);

        if ($safe === null || $safe === '') {
            return null;
        }

        return $safe.'.json';
    }

    /**
     * Remove any stored collection file not present in the kept set.
     *
     * @param  array<string, true>  $keep
     */
    private function pruneExcept(array $keep): void
    {
        foreach ($this->files->glob($this->directory.'/*.json') as $path) {
            if (! isset($keep[basename($path)])) {
                $this->files->delete($path);
            }
        }
    }
}
