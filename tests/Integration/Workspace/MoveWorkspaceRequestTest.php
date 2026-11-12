<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\MoveWorkspaceRequestController;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\MoveWorkspaceRequestRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\MoveWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\MoveWorkspaceRequestData;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(MoveWorkspaceRequestController::class)]
#[CoversClass(MoveWorkspaceRequestRequest::class)]
class MoveWorkspaceRequestTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_input_to_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(MoveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                $this->requestFile($this->savedRequest()),
            );

        // Act

        $this->patchJson(
            route(
                'nimbus.api.workspace.requests.move',
                'request-1234',
            ),
            [
                'folder' => 'archive',
                'name' => 'Old user',
                'hash' => 'a1b2c3d4e5f6a7b8',
            ],
        );

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (MoveWorkspaceRequestData $data): bool {
                $this->assertSame(
                    'request-1234',
                    $data->id,
                );

                $this->assertSame(
                    'archive',
                    (string) $data->folder,
                );

                $this->assertSame(
                    'Old user',
                    $data->name,
                );

                $this->assertSame(
                    'a1b2c3d4e5f6a7b8',
                    $data->hash,
                );

                return true;
            });
    }

    public function test_it_keeps_the_folder_and_the_name_when_none_are_given(): void
    {
        // Arrange

        $actionSpy = $this->spy(MoveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                $this->requestFile($this->savedRequest()),
            );

        // Act

        $this->patchJson(
            route(
                'nimbus.api.workspace.requests.move',
                'request-1234',
            ),
            ['hash' => 'a1b2c3d4e5f6a7b8'],
        );

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (MoveWorkspaceRequestData $data): bool {
                $this->assertNull($data->folder);
                $this->assertNull($data->name);

                return true;
            });
    }

    public function test_it_moves_to_the_root_folder_when_the_folder_is_empty(): void
    {
        // Arrange

        $actionSpy = $this->spy(MoveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                $this->requestFile($this->savedRequest()),
            );

        // Act

        $this->patchJson(
            route(
                'nimbus.api.workspace.requests.move',
                'request-1234',
            ),
            [
                'folder' => '',
                'hash' => 'a1b2c3d4e5f6a7b8',
            ],
        );

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (MoveWorkspaceRequestData $data): bool {
                $this->assertNotNull($data->folder);
                $this->assertTrue($data->folder->isRoot());

                return true;
            });
    }

    public function test_it_returns_the_moved_request_and_its_hash(): void
    {
        // Arrange

        $requestFile = $this->requestFile(
            $this->savedRequest(),
            hash: 'f6e5d4c3b2a1f6e5',
            folder: 'archive',
        );

        $actionSpy = $this->spy(MoveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                $requestFile,
            );

        // Act

        $response = $this->patchJson(
            route(
                'nimbus.api.workspace.requests.move',
                'request-1234',
            ),
            [
                'folder' => 'archive',
                'hash' => 'a1b2c3d4e5f6a7b8',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertExactJson($requestFile->toArray());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_an_invalid_payload(array $payload, string $field): void
    {
        // Arrange

        $actionSpy = $this->spy(MoveWorkspaceRequestAction::class);

        // Act

        $response = $this->patchJson(
            route(
                'nimbus.api.workspace.requests.move',
                'request-1234',
            ),
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
        yield 'hash is missing' => [
            'payload' => ['folder' => 'archive'],
            'field' => 'hash',
        ];

        yield 'hash is too long' => [
            'payload' => ['hash' => str_repeat('a', 65)],
            'field' => 'hash',
        ];

        yield 'name is too long' => [
            'payload' => ['name' => str_repeat('a', 121), 'hash' => 'a1b2c3d4e5f6a7b8'],
            'field' => 'name',
        ];

        yield 'folder has no letter or number' => [
            'payload' => ['folder' => '!!!', 'hash' => 'a1b2c3d4e5f6a7b8'],
            'field' => 'folder',
        ];

        yield 'folder is nested too deep' => [
            'payload' => ['folder' => 'a/b/c/d/e/f', 'hash' => 'a1b2c3d4e5f6a7b8'],
            'field' => 'folder',
        ];
    }

    public function test_it_reports_a_request_that_does_not_exist(): void
    {
        // Arrange

        $actionSpy = $this->spy(MoveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::requestNotFound('request-9999'));

        // Act

        $response = $this->patchJson(
            route(
                'nimbus.api.workspace.requests.move',
                'request-9999',
            ),
            [
                'folder' => 'archive',
                'hash' => 'a1b2c3d4e5f6a7b8',
            ],
        );

        // Assert

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'The request `request-9999` does not exist in the workspace.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        $requestFile = $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        // Act

        $response = $this->patchJson(
            route(
                'nimbus.api.workspace.requests.move',
                'request-1234',
            ),
            [
                'folder' => 'archive',
                'name' => 'Old user',
                'hash' => $requestFile->hash,
            ],
        );

        // Assert

        $response->assertOk();

        $this->assertSame(
            'Old user',
            $response->json('request.name'),
        );

        $this->assertFileExists("{$this->workspacePath}/requests/archive/old-user.request.json");

        $this->assertFileDoesNotExist($requestFile->filepath);
    }
}
