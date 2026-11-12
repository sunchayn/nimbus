<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\DeleteWorkspaceFolderController;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\DeleteWorkspaceFolderRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\DeleteWorkspaceFolderAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DeleteWorkspaceFolderController::class)]
#[CoversClass(DeleteWorkspaceFolderRequest::class)]
class DeleteWorkspaceFolderTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_folder_to_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(DeleteWorkspaceFolderAction::class);

        // Act

        $response = $this->deleteJson(
            route('nimbus.api.workspace.folders.delete'),
            ['folder' => 'Users/Admin'],
        );

        // Assert

        $response->assertNoContent();

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (FolderPath $folder): bool {
                $this->assertSame(
                    'users/admin',
                    (string) $folder,
                );

                return true;
            });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_an_invalid_payload(array $payload): void
    {
        // Arrange

        $actionSpy = $this->spy(DeleteWorkspaceFolderAction::class);

        // Act

        $response = $this->deleteJson(
            route('nimbus.api.workspace.folders.delete'),
            $payload,
        );

        // Assert

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('folder');

        $actionSpy->shouldNotHaveReceived('execute');
    }

    public static function invalidPayloads(): Generator
    {
        yield 'folder is missing' => [
            'payload' => [],
        ];

        yield 'folder is not a string' => [
            'payload' => ['folder' => ['users']],
        ];

        yield 'folder is too long' => [
            'payload' => ['folder' => str_repeat('a', 301)],
        ];

        yield 'folder has no letter or number' => [
            'payload' => ['folder' => '!!!'],
        ];

        yield 'folder is nested too deep' => [
            'payload' => ['folder' => 'a/b/c/d/e/f'],
        ];
    }

    public function test_it_reports_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $actionSpy = $this->spy(DeleteWorkspaceFolderAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::folderNotFound('users'));

        // Act

        $response = $this->deleteJson(
            route('nimbus.api.workspace.folders.delete'),
            ['folder' => 'users'],
        );

        // Assert

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'The folder `users` does not exist in the workspace.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        $this->createFolder('users/admin');

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        // Act

        $response = $this->deleteJson(
            route('nimbus.api.workspace.folders.delete'),
            ['folder' => 'users'],
        );

        // Assert

        $response->assertNoContent();

        $this->assertDirectoryDoesNotExist("{$this->workspacePath}/requests/users");

        $this->assertSame(
            [],
            $this->listFolders(),
        );

        $this->assertSame(
            [],
            $this->listRequestIds(),
        );
    }
}
