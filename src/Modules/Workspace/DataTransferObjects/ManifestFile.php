<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Stringable;
use Sunchayn\Nimbus\Modules\Workspace\Collections\WorkspaceEntriesCollection;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\Concerns\JsonStringable;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\EntryIdentity;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * The content of nimbus.json, which only holds the order of the workspace.
 */
class ManifestFile implements Stringable
{
    use JsonStringable;

    /**
     * @var array<string, list<string>>
     */
    public readonly array $folders;

    /**
     * @var array<string, list<string>>
     */
    public readonly array $requests;

    /**
     * A folder without subfolders or requests has no key, because its name is in the list of its parent.
     *
     * @param  array<string, list<string>>  $folders  The ordered folder names by parent path, with an empty path for the root.
     * @param  array<string, list<string>>  $requests  The ordered request ids by folder path.
     */
    public function __construct(array $folders = [], array $requests = [])
    {
        $this->folders = collect($folders)->filter()->all();
        $this->requests = collect($requests)->filter()->all();
    }

    /**
     * Builds the manifest from entries that are already in the order to save.
     */
    public static function fromEntries(WorkspaceEntriesCollection $entries): self
    {
        return new self(
            folders: $entries->folderNamesByParent(),
            requests: $entries->requestIdsByFolder(),
        );
    }

    public static function fromRaw(?string $json): self
    {
        // A missing or invalid file gives an empty manifest.
        $decoded = $json === null ? null : json_decode($json, true);

        $data = is_array($decoded) ? $decoded : [];

        return new self(
            folders: self::lists($data['folders'] ?? null),
            requests: self::lists($data['requests'] ?? null),
        );
    }

    /**
     * @return array{folders: array<string, list<string>>, requests: array<string, list<string>>}
     */
    public function toArray(): array
    {
        return [
            'folders' => $this->folders,
            'requests' => $this->requests,
        ];
    }

    /**
     * The identities of the folders and requests it lists, in the saved order.
     *
     * @return list<EntryIdentity>
     */
    public function entryIdentities(): array
    {
        $folders = collect($this->folders)
            ->flatMap(static fn (array $names, int|string $parent): array => array_map(
                static fn (string $name): EntryIdentity => EntryIdentity::folder(
                    FolderPath::fromStored((string) $parent)->join($name),
                ),
                $names,
            ));

        $requests = collect($this->requests)
            ->flatMap(static fn (array $ids, int|string $folder): array => array_map(
                static fn (string $id): EntryIdentity => EntryIdentity::request(
                    FolderPath::fromStored((string) $folder),
                    $id,
                ),
                $ids,
            ));

        return [...$folders->all(), ...$requests->all()];
    }

    /**
     * Adds the folder and the folders above it, each at the end of its parent.
     */
    public function addFolder(FolderPath $folder): self
    {
        $folders = $this->folders;

        $parent = FolderPath::root();

        foreach (explode('/', (string) $folder) as $name) {
            $siblings = $folders[(string) $parent] ?? [];

            if (! in_array($name, $siblings, true)) {
                $folders[(string) $parent] = [...$siblings, $name];
            }

            $parent = $parent->join($name);
        }

        return new self(
            folders: $folders,
            requests: $this->requests,
        );
    }

    /**
     * Removes the folder, everything inside it, and its name from the parent list.
     */
    public function removeFolder(FolderPath $folder): self
    {
        $isInsideFolder = fn (string $path): bool => $path === (string) $folder || str_starts_with($path, "{$folder}/");

        $folders = collect($this->folders)
            ->reject(fn (array $names, string $path): bool => $isInsideFolder($path))
            ->all();

        $requests = collect($this->requests)
            ->reject(fn (array $ids, string $path): bool => $isInsideFolder($path))
            ->all();

        // The parent list is kept because other folders can still be in it.
        $parent = (string) $folder->parent();

        $folders[$parent] = array_values(
            collect($folders[$parent] ?? [])->diff([$folder->name()])->all(),
        );

        return new self(
            folders: $folders,
            requests: $requests,
        );
    }

    /**
     * Renames the folder in place, so it keeps its position and everything inside it follows.
     */
    public function withFolderRenamed(FolderPath $from, FolderPath $to): self
    {
        $parent = (string) $from->parent();

        $folders = [];

        foreach ($this->folders as $path => $names) {
            if ((string) $path === $parent) {
                $names = $this->renameName(
                    $names,
                    $from,
                    $to,
                );
            }

            $folders[$this->rekey(
                (string) $path,
                $from,
                $to,
            )] = $names;
        }

        $requests = collect($this->requests)
            ->mapWithKeys(
                fn (array $ids, int|string $path): array => [
                    $this->rekey((string) $path, $from, $to) => $ids,
                ],
            )
            ->all();

        return new self(
            folders: $folders,
            requests: $requests,
        );
    }

    /**
     * Adds the request at the end of its folder list.
     */
    public function addRequest(RequestFile $requestFile): self
    {
        $folder = (string) $requestFile->folder;

        $ids = $this->requests[$folder] ?? [];

        $requests = $this->requests;

        $requests[$folder] = array_values(array_unique([...$ids, $requestFile->savedRequest->id]));

        return new self(
            folders: $this->folders,
            requests: $requests,
        );
    }

    /**
     * Removes the request from its folder list.
     */
    public function removeRequest(RequestFile $requestFile): self
    {
        $folder = (string) $requestFile->folder;

        $requests = $this->requests;

        $requests[$folder] = array_values(
            collect($requests[$folder] ?? [])->diff([$requestFile->savedRequest->id])->all(),
        );

        return new self(
            folders: $this->folders,
            requests: $requests,
        );
    }

    private function rekey(
        string $path,
        FolderPath $from,
        FolderPath $to,
    ): string {
        if ($path === (string) $from) {
            return (string) $to;
        }

        if (! str_starts_with($path, "{$from}/")) {
            return $path;
        }

        $descendant = substr($path, strlen((string) $from));

        return "{$to}{$descendant}";
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function renameName(
        array $names,
        FolderPath $from,
        FolderPath $to,
    ): array {
        return array_map(
            fn (string $name): string => $name === $from->name() ? $to->name() : $name,
            $names,
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private static function lists(mixed $value): array
    {
        $lists = [];

        foreach (is_array($value) ? $value : [] as $path => $entries) {
            if (! is_array($entries)) {
                continue;
            }

            $names = array_values(
                array_unique(
                    array_filter(
                        $entries,
                        fn (mixed $entry): bool => is_string($entry) && $entry !== '',
                    ),
                ),
            );

            if ($names !== []) {
                $lists[(string) $path] = $names;
            }
        }

        return $lists;
    }
}
