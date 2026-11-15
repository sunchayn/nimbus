<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\RenameWorkspaceFolderController;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\RenameWorkspaceFolderRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\RenameWorkspaceFolderAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RenameWorkspaceFolderController::class)]
#[CoversClass(RenameWorkspaceFolderRequest::class)]
class RenameWorkspaceFolderTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_folder_and_the_new_name_to_the_action(): void
    {
        // Arrange

        $actionSpy = $this->spy(RenameWorkspaceFolderAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                FolderPath::from('users/operators'),
            );

        // Act

        $this->patchJson(
            route('nimbus.api.workspace.folders.update'),
            [
                'folder' => 'Users/Admin',
                'name' => 'Operators',
            ],
        );

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (FolderPath $folder, FolderPath $newName): bool {
                $this->assertSame(
                    'users/admin',
                    (string) $folder,
                );

                $this->assertSame(
                    'operators',
                    (string) $newName,
                );

                return true;
            });
    }

    public function test_it_returns_the_renamed_folder(): void
    {
        // Arrange

        $actionSpy = $this->spy(RenameWorkspaceFolderAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                FolderPath::from('users/operators'),
            );

        // Act

        $response = $this->patchJson(
            route('nimbus.api.workspace.folders.update'),
            [
                'folder' => 'users/admin',
                'name' => 'operators',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertExactJson([
                'folder' => 'users/operators',
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_an_invalid_payload(array $payload, string $field): void
    {
        // Arrange

        $actionSpy = $this->spy(RenameWorkspaceFolderAction::class);

        // Act

        $response = $this->patchJson(
            route('nimbus.api.workspace.folders.update'),
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
            'payload' => ['name' => 'team'],
            'field' => 'folder',
        ];

        yield 'folder has no letter or number' => [
            'payload' => ['folder' => '!!!', 'name' => 'team'],
            'field' => 'folder',
        ];

        yield 'folder is nested too deep' => [
            'payload' => ['folder' => 'a/b/c/d/e/f', 'name' => 'team'],
            'field' => 'folder',
        ];

        yield 'name is missing' => [
            'payload' => ['folder' => 'users'],
            'field' => 'name',
        ];

        yield 'name has a slash' => [
            'payload' => ['folder' => 'users', 'name' => 'team/admin'],
            'field' => 'name',
        ];

        yield 'name has no letter or number' => [
            'payload' => ['folder' => 'users', 'name' => '!!!'],
            'field' => 'name',
        ];

        yield 'name is too long' => [
            'payload' => ['folder' => 'users', 'name' => str_repeat('a', 61)],
            'field' => 'name',
        ];
    }

    public function test_it_reports_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $actionSpy = $this->spy(RenameWorkspaceFolderAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::folderNotFound('users'));

        // Act

        $response = $this->patchJson(
            route('nimbus.api.workspace.folders.update'),
            [
                'folder' => 'users',
                'name' => 'team',
            ],
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

        $this->createFolder('billing');

        // Act

        $response = $this->patchJson(
            route('nimbus.api.workspace.folders.update'),
            [
                'folder' => 'users/admin',
                'name' => 'Operators',
            ],
        );

        // Assert

        $response
            ->assertOk()
            ->assertExactJson([
                'folder' => 'users/operators',
            ]);

        $this->assertDirectoryExists("{$this->workspacePath}/requests/users/operators");

        $this->assertDirectoryDoesNotExist("{$this->workspacePath}/requests/users/admin");
    }
}
