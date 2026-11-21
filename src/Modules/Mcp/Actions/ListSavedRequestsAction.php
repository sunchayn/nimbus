<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/** @final */
class ListSavedRequestsAction
{
    public function __construct(private readonly ScanWorkspaceAction $scanWorkspaceAction) {}

    /**
     * @return array{count: int, folders: array<int, string>, requests: array<int, array<string, mixed>>}
     *
     * @throws WorkspaceException
     */
    public function execute(?string $folder = null): array
    {
        $workspace = $this->scanWorkspaceAction->execute();

        $folderPrefix = trim((string) $folder, '/');

        $requests = $workspace
            ->entries
            ->requestFiles()
            ->filter(fn (RequestFile $requestFile): bool => $this->isInsideFolder(
                requestFile: $requestFile,
                folder: $folderPrefix,
            ))
            ->map(fn (RequestFile $requestFile): array => $this->summarize(requestFile: $requestFile))
            ->values()
            ->all();

        $folders = $workspace
            ->entries
            ->folderPaths()
            ->map(static fn (FolderPath $path): string => (string) $path)
            ->values()
            ->all();

        return [
            'count' => count($requests),
            'folders' => $folders,
            'requests' => $requests,
        ];
    }

    private function isInsideFolder(RequestFile $requestFile, string $folder): bool
    {
        if ($folder === '') {
            return true;
        }

        $requestFolder = (string) $requestFile->folder;

        return $requestFolder === $folder || str_starts_with($requestFolder, "{$folder}/");
    }

    /**
     * @return array<string, mixed>
     */
    private function summarize(RequestFile $requestFile): array
    {
        return [
            'id' => $requestFile->savedRequest->id,
            'name' => $requestFile->savedRequest->name,
            'method' => $requestFile->savedRequest->method,
            'endpoint' => $requestFile->savedRequest->endpoint,
            'route' => $requestFile->savedRequest->route,
            'file' => $requestFile->relativeFilepath,
            'folder' => (string) $requestFile->folder,
        ];
    }
}
