<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Concerns;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Workspace\Actions\CreateWorkspaceFolderAction;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReadWorkspaceFileAction;
use Sunchayn\Nimbus\Modules\Workspace\Actions\SaveWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\Actions\TryLockWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\Actions\WriteWorkspaceFileAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SaveWorkspaceRequestData;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceScanResult;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * Points the workspace at a throwaway folder inside the base path.
 */
trait UsesTemporaryWorkspace
{
    protected string $workspacePath = '';

    /**
     * @var array<int, resource>
     */
    private array $lockProcessPipes = [];

    protected function enableWorkspace(): void
    {
        $path = '.nimbus-test-'.Str::uuid();

        $this->workspacePath = base_path($path);

        config()->set(
            'nimbus.workspace.enabled',
            true,
        );

        config()->set(
            'nimbus.workspace.path',
            $path,
        );
    }

    protected function tearDownWorkspace(): void
    {
        if ($this->workspacePath !== '') {
            File::deleteDirectory($this->workspacePath);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function requestPayload(array $overrides = []): array
    {
        return [
            'name' => 'Create user',
            'method' => 'POST',
            'endpoint' => '{{base_url}}/api/users',
            'route' => 'users.store',
            'headers' => [['key' => 'Accept', 'value' => 'application/json', 'enabled' => true]],
            'query' => [],
            'body' => ['type' => 'json', 'content' => '{ "name": "{{$randomName}}" }'],
            'authorization' => ['type' => 'bearer', 'value' => '{{token}}'],
            'hash' => null,
            ...$overrides,
        ];
    }

    protected function savedRequest(
        string $id = 'request-1234',
        string $name = 'Create user',
        string $bodyContent = '{}',
    ): SavedRequest {
        return SavedRequest::fromArray([
            'id' => $id,
            'name' => $name,
            'method' => 'POST',
            'endpoint' => '{{base_url}}/api/users',
            'route' => 'users.store',
            'headers' => [['key' => 'Accept', 'value' => 'application/json', 'enabled' => true]],
            'body' => ['type' => 'json', 'content' => $bodyContent],
            'authorization' => ['type' => 'bearer', 'value' => '{{token}}'],
        ]);
    }

    /**
     * Builds the request file an action would return, without writing anything to the disk.
     */
    protected function requestFile(
        SavedRequest $savedRequest,
        string $hash = 'a1b2c3d4e5f6a7b8',
        string $folder = '',
    ): RequestFile {
        $folderPath = FolderPath::from($folder);

        $directory = collect(['requests', (string) $folderPath])->filter()->implode('/');

        $slug = Str::slug($savedRequest->name);

        $relativeFilepath = "{$directory}/{$slug}".SavedRequest::REQUEST_SUFFIX;

        return new RequestFile(
            filepath: "{$this->workspacePath}/{$relativeFilepath}",
            relativeFilepath: $relativeFilepath,
            folder: $folderPath,
            savedRequest: $savedRequest,
            hash: $hash,
        );
    }

    /**
     * Saves a request through the action, for tests that need a request on the disk.
     */
    protected function saveRequest(
        SavedRequest $savedRequest,
        ?string $hash,
        ?string $folder = null,
    ): RequestFile {
        $data = new SaveWorkspaceRequestData(
            savedRequest: $savedRequest,
            hash: $hash,
            folder: FolderPath::from($folder),
        );

        return resolve(SaveWorkspaceRequestAction::class)->execute(
            data: $data,
        );
    }

    /**
     * Creates a folder through the action, for tests that need a folder on the disk.
     */
    protected function createFolder(string $folder): void
    {
        $folderPath = FolderPath::from($folder);

        resolve(CreateWorkspaceFolderAction::class)->execute(
            folder: $folderPath,
        );
    }

    protected function findRequest(string $id): RequestFile
    {
        $workspace = resolve(ScanWorkspaceAction::class)->execute();

        return $workspace->entries->findRequestOrFail($id);
    }

    /**
     * @return list<string>
     */
    protected function listFolders(): array
    {
        $workspace = resolve(ScanWorkspaceAction::class)->execute();

        return $workspace
            ->entries
            ->folderPaths()
            ->map(strval(...))
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function listRequestIds(): array
    {
        $workspace = resolve(ScanWorkspaceAction::class)->execute();

        return $workspace
            ->entries
            ->requestFiles()
            ->map(static fn (RequestFile $requestFile): string => $requestFile->savedRequest->id)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function listRequestNames(): array
    {
        $workspace = resolve(ScanWorkspaceAction::class)->execute();

        return $workspace
            ->entries
            ->requestFiles()
            ->map(static fn (RequestFile $requestFile): string => $requestFile->savedRequest->name)
            ->values()
            ->all();
    }

    /**
     * @return array{folders: array<string, list<string>>, requests: array<string, list<string>>}
     */
    protected function readManifest(): array
    {
        $contents = (string) file_get_contents("{$this->workspacePath}/nimbus.json");

        /** @var array{folders: array<string, list<string>>, requests: array<string, list<string>>} */
        return json_decode($contents, true);
    }

    /**
     * Whether another file handle is refused the lock, which means the workspace lock is held.
     */
    protected function isWorkspaceLocked(): bool
    {
        $handle = fopen(resolve(WorkspaceConfig::class)->lockPath(), 'c');

        $locked = ! flock($handle, LOCK_EX | LOCK_NB);

        if (! $locked) {
            flock($handle, LOCK_UN);
        }

        fclose($handle);

        return $locked;
    }

    /**
     * Replaces the scan with one that records whether the lock is held each time it runs.
     */
    protected function spyOnScanLockState(): ScanWorkspaceAction
    {
        $isLocked = fn (): bool => $this->isWorkspaceLocked();

        $spy = new class(resolve(WorkspaceConfig::class), resolve(ReadWorkspaceFileAction::class), resolve(WriteWorkspaceFileAction::class), resolve(TryLockWorkspaceAction::class)) extends ScanWorkspaceAction
        {
            /** @var list<bool> */
            public array $lockedAtScan = [];

            public ?Closure $isLocked = null;

            public function execute(): WorkspaceScanResult
            {
                $this->lockedAtScan[] = ($this->isLocked)();

                return parent::execute();
            }
        };

        $spy->isLocked = $isLocked;

        $this->app->instance(
            ScanWorkspaceAction::class,
            $spy,
        );

        return $spy;
    }

    /**
     * Starts a second PHP process that takes the lock and holds it for a while.
     * It returns once that process reports that it holds the lock.
     *
     * @return resource
     */
    protected function holdTheLockInAnotherProcess(int $milliseconds)
    {
        $code = '$h = fopen($argv[1], "c"); flock($h, LOCK_EX); fwrite(STDOUT, "locked\n"); fflush(STDOUT); usleep((int) $argv[2] * 1000);';

        $lockPath = resolve(WorkspaceConfig::class)->lockPath();

        $process = proc_open(
            [PHP_BINARY, '-r', $code, $lockPath, (string) $milliseconds],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        $this->assertIsResource($process);

        $this->assertSame(
            "locked\n",
            fgets($pipes[1]),
        );

        $this->lockProcessPipes = $pipes;

        return $process;
    }

    /**
     * @param  resource  $process
     */
    protected function closeLockProcess($process): void
    {
        foreach ($this->lockProcessPipes as $pipe) {
            fclose($pipe);
        }

        proc_close($process);
    }
}
