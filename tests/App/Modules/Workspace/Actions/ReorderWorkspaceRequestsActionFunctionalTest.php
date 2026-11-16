<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReorderWorkspaceRequestsAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ReorderWorkspaceRequestsAction::class)]
#[CoversClass(WorkspaceException::class)]
class ReorderWorkspaceRequestsActionFunctionalTest extends TestCase
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

    public function test_it_reorders_the_requests_of_a_folder(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(
                'a',
                'Alpha',
            ),
            hash: null,
            folder: 'users',
        );

        $this->saveRequest(
            $this->savedRequest(
                'b',
                'Beta',
            ),
            hash: null,
            folder: 'users',
        );

        $this->saveRequest(
            $this->savedRequest(
                'c',
                'Gamma',
            ),
            hash: null,
            folder: 'users',
        );

        $data = $this->reorderArguments(
            folder: 'users',
            ids: ['c', 'unknown', 'a'],
        );

        $action = resolve(ReorderWorkspaceRequestsAction::class);

        // Act

        $action->execute(...$data);

        // Assert

        $this->assertSame(
            ['c', 'a', 'b'],
            $this->listRequestIds(),
        );

        $this->assertSame(
            ['users' => ['c', 'a', 'b']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_reorders_the_requests_of_the_root(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(
                'a',
                'Alpha',
            ),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(
                'b',
                'Beta',
            ),
            hash: null,
        );

        $data = $this->reorderArguments(
            folder: '',
            ids: ['b', 'a'],
        );

        $action = resolve(ReorderWorkspaceRequestsAction::class);

        // Act

        $action->execute(...$data);

        // Assert

        $this->assertSame(
            ['b', 'a'],
            $this->listRequestIds(),
        );
    }

    public function test_it_ignores_the_requests_of_another_folder(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(
                'a',
                'Alpha',
            ),
            hash: null,
            folder: 'users',
        );

        $this->saveRequest(
            $this->savedRequest(
                'b',
                'Beta',
            ),
            hash: null,
        );

        $data = $this->reorderArguments(
            folder: 'users',
            ids: ['b', 'a'],
        );

        $action = resolve(ReorderWorkspaceRequestsAction::class);

        // Act

        $action->execute(...$data);

        // Assert

        $this->assertSame(
            ['' => ['b'], 'users' => ['a']],
            $this->readManifest()['requests'],
        );
    }

    public function test_it_refuses_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $data = $this->reorderArguments(
            folder: 'missing',
            ids: ['a'],
        );

        $action = resolve(ReorderWorkspaceRequestsAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('does not exist in the workspace');

        // Act

        $action->execute(...$data);
    }

    public function test_it_scans_only_after_taking_the_lock(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $scanSpy = $this->spyOnScanLockState();

        $data = $this->reorderArguments(
            folder: '',
            ids: ['request-1234'],
        );

        $action = resolve(ReorderWorkspaceRequestsAction::class);

        // Act

        $action->execute(...$data);

        // Assert

        $this->assertSame(
            [true],
            $scanSpy->lockedAtScan,
        );
    }

    /**
     * @param  list<string>  $ids
     */
    private function reorderArguments(string $folder, array $ids): array
    {
        return [
            'folder' => FolderPath::from($folder),
            'ids' => $ids,
        ];
    }
}
