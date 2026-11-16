<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Workspace\Actions\LockWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\Actions\TryLockWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\Services\WorkspaceLockFile;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(TryLockWorkspaceAction::class)]
#[CoversClass(WorkspaceLockFile::class)]
class TryLockWorkspaceActionFunctionalTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableWorkspace();
    }

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_does_not_run_the_callback_when_another_handle_holds_the_lock(): void
    {
        // Arrange

        $handle = fopen(resolve(WorkspaceConfig::class)->lockPath(), 'c');

        flock($handle, LOCK_EX);

        $ran = false;

        $action = resolve(TryLockWorkspaceAction::class);

        // Act

        $result = $action->execute(function () use (&$ran): void {
            $ran = true;
        });

        // Assert

        $this->assertFalse($result);
        $this->assertFalse($ran);

        flock($handle, LOCK_UN);

        fclose($handle);
    }

    public function test_it_runs_the_callback_when_the_lock_is_free_and_releases_it(): void
    {
        // Arrange

        $action = resolve(TryLockWorkspaceAction::class);

        // Act

        $ran = $action->execute(fn (): bool => $this->isWorkspaceLocked());

        // Assert

        $this->assertTrue($ran);
        $this->assertFalse($this->isWorkspaceLocked());
    }

    public function test_it_runs_the_callback_inside_a_lock_the_process_already_holds(): void
    {
        // Arrange

        $lockAction = resolve(LockWorkspaceAction::class);

        $action = resolve(TryLockWorkspaceAction::class);

        // Act

        $ran = $lockAction->execute(
            fn (): bool => $action->execute(fn (): null => null),
        );

        // Assert

        $this->assertTrue($ran);
    }

    public function test_it_does_not_run_the_callback_while_another_process_holds_the_lock(): void
    {
        // Arrange

        $process = $this->holdTheLockInAnotherProcess(milliseconds: 600);

        $action = resolve(TryLockWorkspaceAction::class);

        // Act

        $ran = $action->execute(fn (): null => null);

        // Assert

        $this->assertFalse($ran);

        $this->closeLockProcess($process);

        $this->assertTrue($action->execute(fn (): null => null));
    }

    public function test_it_releases_the_lock_when_the_callback_throws(): void
    {
        // Arrange

        $action = resolve(TryLockWorkspaceAction::class);

        // Act

        try {
            $action->execute(fn () => throw new RuntimeException('Boom'));
        } catch (RuntimeException) {
        }

        // Assert

        $this->assertFalse($this->isWorkspaceLocked());
    }
}
