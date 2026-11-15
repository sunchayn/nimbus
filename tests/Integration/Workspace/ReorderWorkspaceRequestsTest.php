<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ReorderWorkspaceRequestsController;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\ReorderWorkspaceRequestsRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReorderWorkspaceRequestsAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ReorderWorkspaceRequestsController::class)]
#[CoversClass(ReorderWorkspaceRequestsRequest::class)]
class ReorderWorkspaceRequestsTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_folder_and_the_ids_to_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(ReorderWorkspaceRequestsAction::class);

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.requests.reorder'),
            [
                'folder' => 'Users',
                'requests' => ['request-beta', 'request-alpha'],
            ],
        );

        // Assert

        $response->assertNoContent();

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (FolderPath $folder, array $ids): bool {
                $this->assertSame(
                    'users',
                    (string) $folder,
                );

                $this->assertSame(
                    ['request-beta', 'request-alpha'],
                    $ids,
                );

                return true;
            });
    }

    public function test_it_reorders_the_root_folder_when_no_folder_is_given(): void
    {
        // Arrange

        $actionSpy = $this->spy(ReorderWorkspaceRequestsAction::class);

        // Act

        $this->putJson(
            route('nimbus.api.workspace.requests.reorder'),
            ['requests' => ['request-beta']],
        );

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (FolderPath $folder, array $ids): bool {
                $this->assertTrue($folder->isRoot());

                return true;
            });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_an_invalid_payload(array $payload, string $field): void
    {
        // Arrange

        $actionSpy = $this->spy(ReorderWorkspaceRequestsAction::class);

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.requests.reorder'),
            $payload,
        );

        // Assert

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $actionSpy->shouldNotHaveReceived('execute');
    }

    public static function invalidPayloads(): Generator
    {
        yield 'requests are missing' => [
            'payload' => ['folder' => ''],
            'field' => 'requests',
        ];

        yield 'requests are empty' => [
            'payload' => ['folder' => '', 'requests' => []],
            'field' => 'requests',
        ];

        yield 'requests are too many' => [
            'payload' => ['folder' => '', 'requests' => array_fill(0, 1001, 'request-1234')],
            'field' => 'requests',
        ];

        yield 'a request id is too long' => [
            'payload' => ['folder' => '', 'requests' => [str_repeat('a', 101)]],
            'field' => 'requests.0',
        ];

        yield 'folder has no letter or number' => [
            'payload' => ['folder' => '!!!', 'requests' => ['request-1234']],
            'field' => 'folder',
        ];

        yield 'folder is nested too deep' => [
            'payload' => ['folder' => 'a/b/c/d/e/f', 'requests' => ['request-1234']],
            'field' => 'folder',
        ];
    }

    public function test_it_reports_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $actionSpy = $this->spy(ReorderWorkspaceRequestsAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::folderNotFound('missing'));

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.requests.reorder'),
            [
                'folder' => 'missing',
                'requests' => ['request-1234'],
            ],
        );

        // Assert

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'The folder `missing` does not exist in the workspace.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-alpha',
                name: 'Alpha',
            ),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-bravo',
                name: 'Bravo',
            ),
            hash: null,
        );

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.requests.reorder'),
            [
                'folder' => '',
                'requests' => ['request-bravo', 'request-alpha'],
            ],
        );

        // Assert

        $response->assertNoContent();

        $this->assertSame(
            ['request-bravo', 'request-alpha'],
            $this->listRequestIds(),
        );
    }
}
