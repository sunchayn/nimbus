<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\DeleteWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DeleteWorkspaceRequestAction::class)]
class DeleteWorkspaceRequestActionFunctionalTest extends TestCase
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

    public function test_it_deletes_a_request(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $action = resolve(DeleteWorkspaceRequestAction::class);

        // Act

        $action->execute(id: 'request-1234');

        // Assert

        $this->assertFileDoesNotExist("{$this->workspacePath}/requests/create-user.request.json");

        $this->assertSame(
            [],
            $this->listRequestIds(),
        );
    }

    public function test_it_removes_a_deleted_request_from_the_manifest(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-aaaa',
                name: 'First',
            ),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-bbbb',
                name: 'Second',
            ),
            hash: null,
        );

        $action = resolve(DeleteWorkspaceRequestAction::class);

        // Act

        $action->execute(id: 'request-aaaa');

        // Assert

        $this->assertSame(
            ['' => ['request-bbbb']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_refuses_a_request_that_does_not_exist(): void
    {
        // Arrange

        $action = resolve(DeleteWorkspaceRequestAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('request-9999');

        // Act

        $action->execute(id: 'request-9999');
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $scanSpy = $this->spyOnScanLockState();

        $action = resolve(DeleteWorkspaceRequestAction::class);

        // Act

        $action->execute(id: 'request-1234');

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }
}
