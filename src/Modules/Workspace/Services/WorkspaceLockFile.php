<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Services;

use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/**
 * Holds the exclusive locks of the workspace for the current process.
 */
class WorkspaceLockFile
{
    /**
     * @var array<string, array{handle: resource, depth: int}>
     */
    private array $held = [];

    /**
     * @return bool False when another process holds the lock and the call is not allowed to wait.
     *
     * @throws WorkspaceException
     */
    public function acquire(string $path, bool $wait): bool
    {
        // Entering again in the same process never waits, so nested calls cannot deadlock.
        if (isset($this->held[$path])) {
            $this->held[$path]['depth']++;

            return true;
        }

        $handle = $this->open($path);

        if ($this->lock(
            $handle,
            $wait,
        )) {
            $this->held[$path] = ['handle' => $handle, 'depth' => 1];

            return true;
        }

        fclose($handle);

        if ($wait) {
            throw WorkspaceException::writeFailed();
        }

        return false;
    }

    public function release(string $path): void
    {
        if (! isset($this->held[$path])) {
            return;
        }

        $this->held[$path]['depth']--;

        if ($this->held[$path]['depth'] > 0) {
            return;
        }

        $handle = $this->held[$path]['handle'];

        flock($handle, LOCK_UN);

        fclose($handle);

        unset($this->held[$path]);
    }

    /**
     * @return resource
     *
     * @throws WorkspaceException
     */
    private function open(string $path)
    {
        $handle = @fopen($path, 'c');

        if ($handle === false) {
            throw WorkspaceException::writeFailed();
        }

        return $handle;
    }

    /**
     * @param  resource  $handle
     */
    private function lock($handle, bool $wait): bool
    {
        if ($wait) {
            return flock($handle, LOCK_EX);
        }

        return flock($handle, LOCK_EX | LOCK_NB);
    }
}
