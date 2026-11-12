<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\ValueObjects;

use Illuminate\Contracts\Config\Repository;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

class WorkspaceConfig
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $path,
    ) {}

    public static function fromConfig(Repository $repository): self
    {
        return new self(
            enabled: (bool) $repository->get(
                'nimbus.workspace.enabled',
                false,
            ),
            path: (string) $repository->get('nimbus.workspace.path'),
        );
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * The workspace folder, which the config gives relative to the application root.
     *
     * @throws WorkspaceException
     */
    public function workspaceDirectory(): string
    {
        if (! $this->enabled) {
            throw WorkspaceException::disabled();
        }

        return base_path($this->path);
    }

    /**
     * @throws WorkspaceException
     */
    public function manifestPath(): string
    {
        $root = $this->workspaceDirectory();
        $manifestFile = SavedRequest::MANIFEST_FILE_PATH;

        return "{$root}/{$manifestFile}";
    }

    /**
     * @throws WorkspaceException
     */
    public function lockPath(): string
    {
        $hash = hash('xxh3', $this->workspaceDirectory());

        // The lock file stays outside the repository, so it never shows up in git.
        return sys_get_temp_dir()."/nimbus-workspace-{$hash}.lock";
    }

    /**
     * @throws WorkspaceException
     */
    public function requestsDirectory(): string
    {
        $root = $this->workspaceDirectory();

        $requestsDirectory = SavedRequest::REQUESTS_DIRECTORY;

        return "{$root}/{$requestsDirectory}";
    }

    /**
     * The directory of a folder, which may not exist yet.
     *
     * @throws WorkspaceException
     */
    public function folderDirectory(FolderPath $folder): string
    {
        $requestsPath = $this->requestsDirectory();

        if ($folder->isRoot()) {
            return $requestsPath;
        }

        return "{$requestsPath}/{$folder}";
    }

    /**
     * The folder that holds a request file.
     *
     * @throws WorkspaceException
     */
    public function folderOf(string $filePath): FolderPath
    {
        $requestsPath = $this->requestsDirectory();

        $relativePath = substr($filePath, strlen("{$requestsPath}/"));

        return FolderPath::fromStored(
            path: dirname($relativePath),
        );
    }

    /**
     * A path of the workspace relative to its root, as it is shown to the user.
     *
     * @throws WorkspaceException
     */
    public function relativeFilepath(string $path): string
    {
        $root = $this->workspaceDirectory();

        return substr($path, strlen($root) + 1);
    }
}
