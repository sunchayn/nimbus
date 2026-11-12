<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use FilesystemIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Sunchayn\Nimbus\Modules\Workspace\Collections\WorkspaceEntriesCollection;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceEntry;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceScanResult;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * Reads the folders and the request files of the workspace with the saved order applied.
 *
 * @final
 */
class ScanWorkspaceAction
{
    public function __construct(
        private readonly WorkspaceConfig $config,
        private readonly ReadWorkspaceFileAction $readWorkspaceFileAction,
        private readonly WriteWorkspaceFileAction $writeWorkspaceFileAction,
        private readonly TryLockWorkspaceAction $tryLockWorkspaceAction,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(): WorkspaceScanResult
    {
        $diskEntries = $this->readDiskEntries();

        $savedManifest = $this->readSavedManifest();

        $entries = $this
            ->readEntries($diskEntries)
            ->sortByManifest($savedManifest)
            ->flattenedByHierarchy();

        $manifest = ManifestFile::fromEntries($entries);

        // The manifest is rebuilt from the disk, so it self-heals after files are changed by hand.
        $this->saveIfDirty(
            current: $manifest,
            previous: $savedManifest,
        );

        return new WorkspaceScanResult(
            manifest: $manifest,
            entries: $entries,
        );
    }

    /**
     * @return list<SplFileInfo>
     */
    private function readDiskEntries(): array
    {
        $requestsDirectory = $this->config->requestsDirectory();

        if (! File::isDirectory($requestsDirectory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $requestsDirectory,
                FilesystemIterator::SKIP_DOTS,
            ),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $iterator->setMaxDepth(SavedRequest::MAX_FOLDER_DEPTH);

        $diskEntries = [];

        foreach ($iterator as $diskEntry) {
            // Symlinks are categorically rejected to not deal with things pointing to the wrong files.
            if (! $diskEntry->isLink()) {
                $diskEntries[] = $diskEntry;
            }
        }

        return $diskEntries;
    }

    private function readSavedManifest(): ManifestFile
    {
        $rawContent = $this->readWorkspaceFileAction->execute(
            filepath: $this->config->manifestPath(),
        );

        return ManifestFile::fromRaw($rawContent);
    }

    /**
     * @param  list<SplFileInfo>  $diskEntries
     *
     * @throws WorkspaceException
     */
    private function readEntries(array $diskEntries): WorkspaceEntriesCollection
    {
        $entries = collect($diskEntries)
            ->map(fn (SplFileInfo $diskEntry): ?WorkspaceEntry => $this->toEntry($diskEntry))
            ->filter()
            ->values()
            ->all();

        return WorkspaceEntriesCollection::make($entries);
    }

    /**
     * @throws WorkspaceException
     */
    private function toEntry(SplFileInfo $diskEntry): ?WorkspaceEntry
    {
        if ($diskEntry->isDir()) {
            return WorkspaceEntry::folder(
                folder: $this->folderPathOf($diskEntry),
            );
        }

        if (! $this->isRequestFile($diskEntry)) {
            return null;
        }

        $requestFile = $this->readRequestFile($diskEntry->getPathname());

        if (! $requestFile instanceof RequestFile) {
            return null;
        }

        return WorkspaceEntry::request($requestFile);
    }

    private function folderPathOf(SplFileInfo $directory): FolderPath
    {
        $requestsDirectory = $this->config->requestsDirectory();

        $relativeDirpath = substr($directory->getPathname(), strlen($requestsDirectory) + 1);

        return FolderPath::fromStored($relativeDirpath);
    }

    private function isRequestFile(SplFileInfo $diskEntry): bool
    {
        if (! $diskEntry->isFile()) {
            return false;
        }

        return Str::endsWith(
            $diskEntry->getFilename(),
            SavedRequest::REQUEST_SUFFIX,
        );
    }

    /**
     * @throws WorkspaceException
     */
    private function readRequestFile(string $filepath): ?RequestFile
    {
        $contents = $this->readWorkspaceFileAction->execute(
            filepath: $filepath,
        );

        if ($contents === null) {
            return null;
        }

        // A broken request file is skipped so it cannot hide the others.
        try {
            $savedRequest = SavedRequest::fromRaw($contents);
        } catch (WorkspaceException) {
            return null;
        }

        return new RequestFile(
            filepath: $filepath,
            relativeFilepath: $this->config->relativeFilepath($filepath),
            folder: $this->config->folderOf($filepath),
            savedRequest: $savedRequest,
            hash: hash('xxh3', $contents),
        );
    }

    private function saveIfDirty(
        ManifestFile $current,
        ManifestFile $previous,
    ): void {
        if ($current->toArray() === $previous->toArray()) {
            return;
        }

        try {
            // A busy lock or a failed write skips the save,
            // so a read never fails because of it.
            $this->tryLockWorkspaceAction->execute(
                fn (): string => $this->writeWorkspaceFileAction->execute(
                    data: WriteWorkspaceFileData::fromManifest($current),
                ),
            );
        } catch (WorkspaceException) {
            // The next scan tries again.
        }
    }
}
