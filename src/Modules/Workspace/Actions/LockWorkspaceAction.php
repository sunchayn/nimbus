<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Closure;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\Services\WorkspaceLockFile;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

/**
 * Runs a callback while holding the exclusive lock of the workspace.
 *
 * @final
 */
class LockWorkspaceAction
{
    public function __construct(
        private readonly WorkspaceConfig $config,
        private readonly WorkspaceLockFile $lockFile,
    ) {}

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws WorkspaceException
     */
    public function execute(Closure $callback): mixed
    {
        $path = $this->config->lockPath();

        // Waits for other processes, and a nested call in the same process passes straight through.
        $this->lockFile->acquire(
            $path,
            wait: true,
        );

        try {
            return $callback();
        } finally {
            $this->lockFile->release($path);
        }
    }
}
