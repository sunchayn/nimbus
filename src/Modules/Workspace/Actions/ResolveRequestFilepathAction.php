<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * Resolves the target filepath for a given request.
 *
 * @final
 */
class ResolveRequestFilepathAction
{
    public function __construct(
        private readonly WorkspaceConfig $config,
    ) {}

    /**
     * @throws WorkspaceException
     */
    public function execute(
        ?FolderPath $folder,
        ?string $name,
        ?RequestFile $current = null,
    ): string {
        $currentFilepath = $current?->filepath;

        $targetFolder = $this->findFolder(
            folder: $folder,
            current: $current,
        );

        $dirpath = $this->config->folderDirectory($targetFolder);

        $slug = $this->findSlug(
            name: $name,
            current: $current,
        );

        $filepath = "{$dirpath}/{$slug}".SavedRequest::REQUEST_SUFFIX;

        // A number is added when another file uses the name, and the current file does not count.
        for ($number = 2; $this->isTaken(
            $filepath,
            $currentFilepath,
        ); $number++) {
            $filepath = "{$dirpath}/{$slug}-{$number}".SavedRequest::REQUEST_SUFFIX;
        }

        return $filepath;
    }

    private function isTaken(string $filepath, ?string $current): bool
    {
        if ($filepath === $current) {
            return false;
        }

        return File::exists($filepath);
    }

    private function findFolder(
        ?FolderPath $folder,
        ?RequestFile $current,
    ): FolderPath {
        if ($folder instanceof FolderPath) {
            return $folder;
        }

        if ($current instanceof RequestFile) {
            return $current->folder;
        }

        return FolderPath::root();
    }

    private function findSlug(
        ?string $name,
        ?RequestFile $current,
    ): string {
        if ($name !== null) {
            return $this->slugify($name);
        }

        if ($current instanceof RequestFile) {
            return basename(
                path: $current->filepath,
                suffix: SavedRequest::REQUEST_SUFFIX,
            );
        }

        return $this->slugify('');
    }

    private function slugify(string $name): string
    {
        $slug = Str::slug($name);

        $slug = Str::limit(
            $slug,
            80,
            '',
        );

        if ($slug === '') {
            return 'request';
        }

        return $slug;
    }
}
