<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Actions;

use Closure;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceLockFile;

/**
 * Runs a callback while holding the lock of the workspace, and never waits for it.
 *
 * @final
 */
class TryLockWorkspaceAction
{
    public function __construct(
        private readonly WorkspaceConfig $config,
        private readonly WorkspaceLockFile $lockFile,
    ) {}

    /**
     * @param  Closure(): mixed  $callback
     * @return bool False when another process holds the lock and the callback did not run.
     *
     * @throws WorkspaceException
     */
    public function execute(Closure $callback): bool
    {
        $path = $this->config->lockPath();

        // A nested call in the same process passes straight through.
        $acquired = $this->lockFile->acquire(
            $path,
            wait: false,
        );

        if (! $acquired) {
            return false;
        }

        try {
            $callback();

            return true;
        } finally {
            $this->lockFile->release($path);
        }
    }
}
