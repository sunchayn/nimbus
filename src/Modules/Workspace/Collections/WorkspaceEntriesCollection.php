<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Collections;

use Illuminate\Support\Collection;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceEntry;
use Sunchayn\Nimbus\Modules\Workspace\Enums\EntryKind;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\EntryIdentity;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * The folders and the request files of the workspace.
 *
 * @extends Collection<int, WorkspaceEntry>
 */
class WorkspaceEntriesCollection extends Collection
{
    /**
     * @return Collection<int, FolderPath>
     */
    public function folderPaths(): Collection
    {
        return $this
            ->filter(static fn (WorkspaceEntry $entry): bool => $entry->isFolder())
            ->toBase()
            ->map(static fn (WorkspaceEntry $entry): FolderPath => $entry->folderPath())
            ->values();
    }

    /**
     * @return Collection<int, RequestFile>
     */
    public function requestFiles(): Collection
    {
        return $this
            ->toBase()
            ->flatMap(static fn (WorkspaceEntry $entry): array => $entry->isRequest() ? [$entry->requestFile] : []);
    }

    public function findRequest(string $id): ?RequestFile
    {
        return $this->requestFiles()
            ->first(
                static fn (RequestFile $requestFile): bool => $requestFile->savedRequest->id === $id,
            );
    }

    /**
     * @throws WorkspaceException
     */
    public function findRequestOrFail(string $id): RequestFile
    {
        return $this->findRequest($id) ?? throw WorkspaceException::requestNotFound($id);
    }

    /**
     * Whether the folder is in the workspace. The root folder always is.
     */
    public function hasFolder(FolderPath $folder): bool
    {
        if ($folder->isRoot()) {
            return true;
        }

        return $this->contains(
            static fn (WorkspaceEntry $entry): bool => $entry->isFolder() && $entry->folderPath()->equals($folder),
        );
    }

    /**
     * The entries directly inside the folder.
     */
    public function whereChildOf(FolderPath $parent): static
    {
        return $this
            ->filter(static fn (WorkspaceEntry $entry): bool => $entry->parent->equals($parent))
            ->values();
    }

    /**
     * The entries the manifest lists come first in the saved order and the others follow by name.
     */
    public function sortByManifest(ManifestFile $manifest): static
    {
        return $this
            ->sort($this->rearrangeEntryKind(...))
            ->sortByExactOrder(preOrderedIds: $manifest->entryIdentities());
    }

    /**
     * The listed folders come first in the wanted order. The other entries keep their place.
     *
     * @param  list<FolderPath>  $orderedList
     */
    public function withFoldersOrdered(array $orderedList): static
    {
        $identities = array_map(EntryIdentity::folder(...), $orderedList);

        return $this->sortByExactOrder($identities);
    }

    /**
     * The listed requests of the folder come first in the wanted order. The other entries keep their place.
     *
     * @param  list<string>  $orderedList  An id that is not in the folder is ignored.
     */
    public function withRequestsOrdered(FolderPath $folder, array $orderedList): static
    {
        $identities = array_map(
            static fn (string $id): EntryIdentity => EntryIdentity::request($folder, $id),
            $orderedList,
        );

        return $this->sortByExactOrder($identities);
    }

    /**
     * The requests of a folder come first, then each folder followed by what is inside it.
     */
    public function flattenedByHierarchy(): static
    {
        return $this->descendantsOf(FolderPath::root());
    }

    /**
     * The folder names by parent path, as the manifest stores them.
     *
     * @return array<string, list<string>>
     */
    public function folderNamesByParent(): array
    {
        return $this->keysByParent(EntryKind::Folder);
    }

    /**
     * The request ids by folder path, as the manifest stores them.
     *
     * @return array<string, list<string>>
     */
    public function requestIdsByFolder(): array
    {
        return $this->keysByParent(EntryKind::Request);
    }

    /**
     * @return array<string, list<string>>
     */
    private function keysByParent(EntryKind $kind): array
    {
        $keys = [];

        foreach ($this as $entry) {
            if ($entry->kind === $kind) {
                $keys[(string) $entry->parent][] = $entry->key;
            }
        }

        return $keys;
    }

    private function descendantsOf(FolderPath $parent): static
    {
        $children = $this->whereChildOf($parent);

        $requests = $children
            ->filter(static fn (WorkspaceEntry $entry): bool => $entry->isRequest())
            ->values()
            ->all();

        $folders = $children
            ->filter(static fn (WorkspaceEntry $entry): bool => $entry->isFolder())
            ->flatMap(fn (WorkspaceEntry $folder): array => [
                $folder,
                ...$this->descendantsOf($folder->folderPath())->all(),
            ])
            ->all();

        return static::make([...$requests, ...$folders]);
    }

    /**
     * @param  list<EntryIdentity>  $preOrderedIds  Entry identities in the order they should be listed.
     */
    private function sortByExactOrder(array $preOrderedIds): static
    {
        $positions = array_flip(array_map(strval(...), $preOrderedIds));

        return $this
            // The sort is stable, so the entries that are not wanted keep their place.
            ->sortBy(static fn (WorkspaceEntry $entry): int => $positions[(string) $entry->identity()] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Folders go before requests. Folders sort by name and requests by their label, then by file.
     */
    private function rearrangeEntryKind(WorkspaceEntry $a, WorkspaceEntry $b): int
    {
        if ($a->isRequest() && $b->isRequest()) {
            return strcasecmp($a->requestFile->savedRequest->name, $b->requestFile->savedRequest->name)
                ?: strcmp($a->requestFile->filepath, $b->requestFile->filepath);
        }

        if ($a->kind !== $b->kind) {
            return $a->isFolder() ? -1 : 1;
        }

        return strcmp($a->key, $b->key);
    }
}
