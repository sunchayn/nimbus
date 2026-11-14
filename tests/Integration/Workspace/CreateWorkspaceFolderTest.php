<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\CreateWorkspaceFolderController;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\CreateWorkspaceFolderRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\CreateWorkspaceFolderAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(CreateWorkspaceFolderController::class)]
#[CoversClass(CreateWorkspaceFolderRequest::class)]
class CreateWorkspaceFolderTest extends TestCase
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

        $actionSpy = $this->spy(CreateWorkspaceFolderAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                FolderPath::from('users/admin'),
            );

        // Act

        $this->postJson(
            route('nimbus.api.workspace.folders.create'),
            ['folder' => 'Users/Admin'],
        );

        // Assert

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

    public function test_it_returns_the_created_folder(): void
    {
        // Arrange

        $actionSpy = $this->spy(CreateWorkspaceFolderAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                FolderPath::from('users/admin'),
            );

        // Act

        $response = $this->postJson(
            route('nimbus.api.workspace.folders.create'),
            ['folder' => 'users/admin'],
        );

        // Assert

        $response
            ->assertCreated()
            ->assertExactJson(['folder' => 'users/admin']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_an_invalid_payload(array $payload, string $field): void
    {
        // Arrange

        $actionSpy = $this->spy(CreateWorkspaceFolderAction::class);

        // Act

        $response = $this->postJson(
            route('nimbus.api.workspace.folders.create'),
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
        yield 'folder is missing' => [
            'payload' => [],
            'field' => 'folder',
        ];

        yield 'folder is not a string' => [
            'payload' => ['folder' => ['users']],
            'field' => 'folder',
        ];

        yield 'folder is too long' => [
            'payload' => ['folder' => str_repeat('a', 301)],
            'field' => 'folder',
        ];

        yield 'folder has no letter or number' => [
            'payload' => ['folder' => '!!!'],
            'field' => 'folder',
        ];

        yield 'a folder name is too long' => [
            'payload' => ['folder' => 'users/'.str_repeat('a', 61)],
            'field' => 'folder',
        ];

        yield 'folder is nested too deep' => [
            'payload' => ['folder' => 'a/b/c/d/e/f'],
            'field' => 'folder',
        ];
    }

    public function test_it_reports_a_folder_that_already_exists(): void
    {
        // Arrange

        $actionSpy = $this->spy(CreateWorkspaceFolderAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::folderExists('users'));

        // Act

        $response = $this->postJson(
            route('nimbus.api.workspace.folders.create'),
            ['folder' => 'users'],
        );

        // Assert

        $response
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'A folder or a file named `users` already exists in the workspace.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        // Act

        $response = $this->postJson(
            route('nimbus.api.workspace.folders.create'),
            ['folder' => 'Users/Admin'],
        );

        // Assert

        $response
            ->assertCreated()
            ->assertExactJson(['folder' => 'users/admin']);

        $this->assertDirectoryExists("{$this->workspacePath}/requests/users/admin");

        $this->assertSame(
            ['users', 'users/admin'],
            $this->listFolders(),
        );
    }
}
