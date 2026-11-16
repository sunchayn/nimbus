<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\Services\WorkspaceLockFile;

#[CoversClass(WorkspaceLockFile::class)]
#[CoversClass(WorkspaceException::class)]
class WorkspaceLockFileUnitTest extends TestCase
{
    private string $lockPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockPath = sys_get_temp_dir().'/nimbus-lock-file-test-'.uniqid().'.lock';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->lockPath)) {
            unlink($this->lockPath);
        }

        parent::tearDown();
    }

    public function test_it_acquires_a_free_lock_and_holds_it_until_released(): void
    {
        // Arrange

        $lockFile = new WorkspaceLockFile;

        // Act

        $acquired = $lockFile->acquire(
            $this->lockPath,
            wait: false,
        );

        // Assert

        $this->assertTrue($acquired);
        $this->assertTrue($this->isHeldByAnotherHandle());

        $lockFile->release($this->lockPath);

        $this->assertFalse($this->isHeldByAnotherHandle());
    }

    public function test_it_does_not_wait_when_another_handle_holds_the_lock(): void
    {
        // Arrange

        $otherHandle = fopen($this->lockPath, 'c');

        flock($otherHandle, LOCK_EX);

        $lockFile = new WorkspaceLockFile;

        // Act

        $acquired = $lockFile->acquire(
            $this->lockPath,
            wait: false,
        );

        // Assert

        $this->assertFalse($acquired);

        flock($otherHandle, LOCK_UN);

        fclose($otherHandle);
    }

    public function test_it_lets_the_same_process_enter_again_and_releases_on_the_last_exit(): void
    {
        // Arrange

        $lockFile = new WorkspaceLockFile;

        $lockFile->acquire(
            $this->lockPath,
            wait: true,
        );

        // Act

        $enteredAgain = $lockFile->acquire(
            $this->lockPath,
            wait: false,
        );

        $lockFile->release($this->lockPath);

        // Assert

        $this->assertTrue($enteredAgain);
        $this->assertTrue($this->isHeldByAnotherHandle());

        $lockFile->release($this->lockPath);

        $this->assertFalse($this->isHeldByAnotherHandle());
    }

    public function test_it_ignores_a_release_of_a_lock_it_does_not_hold(): void
    {
        // Arrange

        $lockFile = new WorkspaceLockFile;

        // Act

        $lockFile->release($this->lockPath);

        // Assert

        $this->assertFileDoesNotExist($this->lockPath);
    }

    public function test_it_fails_when_the_lock_file_cannot_be_opened(): void
    {
        // Arrange

        $lockFile = new WorkspaceLockFile;

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('could not be written');

        // Act

        $lockFile->acquire(
            '/nimbus-missing-directory/workspace.lock',
            wait: true,
        );
    }

    private function isHeldByAnotherHandle(): bool
    {
        $handle = fopen($this->lockPath, 'c');

        $held = ! flock($handle, LOCK_EX | LOCK_NB);

        if (! $held) {
            flock($handle, LOCK_UN);
        }

        fclose($handle);

        return $held;
    }
}
