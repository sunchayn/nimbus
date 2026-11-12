<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Workspace\Actions\LockWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceLockFile;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(LockWorkspaceAction::class)]
#[CoversClass(WorkspaceLockFile::class)]
class LockWorkspaceActionFunctionalTest extends TestCase
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

    public function test_it_holds_the_lock_while_the_callback_runs_and_returns_its_result(): void
    {
        // Arrange

        $action = resolve(LockWorkspaceAction::class);

        // Act

        $result = $action->execute(fn (): array => [$this->isWorkspaceLocked()]);

        // Assert

        $this->assertSame(
            [true],
            $result,
        );

        $this->assertFalse($this->isWorkspaceLocked());
    }

    public function test_it_releases_the_lock_when_the_callback_throws(): void
    {
        // Arrange

        $action = resolve(LockWorkspaceAction::class);

        // Act

        try {
            $action->execute(fn () => throw new RuntimeException('Boom'));
        } catch (RuntimeException) {
        }

        // Assert

        $this->assertFalse($this->isWorkspaceLocked());
    }

    public function test_it_lets_the_exception_of_the_callback_through(): void
    {
        // Arrange

        $action = resolve(LockWorkspaceAction::class);

        // Assert

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Boom');

        // Act

        $action->execute(fn () => throw new RuntimeException('Boom'));
    }

    public function test_it_can_be_entered_again_without_waiting_and_stays_held_until_the_outer_call_ends(): void
    {
        // Arrange

        $action = resolve(LockWorkspaceAction::class);

        // Act

        $result = $action->execute(function () use ($action): array {
            $inner = $action->execute(fn (): string => 'inner');

            return [$inner, $this->isWorkspaceLocked()];
        });

        // Assert

        $this->assertSame(
            ['inner', true],
            $result,
        );

        $this->assertFalse($this->isWorkspaceLocked());
    }

    public function test_it_waits_for_another_process_that_holds_the_lock(): void
    {
        // Arrange

        $process = $this->holdTheLockInAnotherProcess(milliseconds: 600);

        $action = resolve(LockWorkspaceAction::class);

        $started = hrtime(true);

        // Act

        $ran = $action->execute(fn (): bool => true);

        // Assert

        $waited = (hrtime(true) - $started) / 1_000_000;

        $this->assertTrue($ran);

        $this->assertGreaterThanOrEqual(
            300,
            $waited,
        );

        $this->closeLockProcess($process);
    }
}
