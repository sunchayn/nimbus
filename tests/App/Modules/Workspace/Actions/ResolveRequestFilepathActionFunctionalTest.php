<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ResolveRequestFilepathAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResolveRequestFilepathAction::class)]
class ResolveRequestFilepathActionFunctionalTest extends TestCase
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

    public function test_it_resolves_the_filepath_from_the_slug_of_the_name(): void
    {
        // Arrange

        $data = $this->resolveArguments(
            folder: null,
            name: 'Create User!',
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/create-user.request.json",
            $filepath,
        );
    }

    public function test_it_resolves_the_filepath_inside_the_folder(): void
    {
        // Arrange

        $data = $this->resolveArguments(
            folder: 'users/admin',
            name: 'Create user',
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/users/admin/create-user.request.json",
            $filepath,
        );
    }

    public function test_it_falls_back_to_a_generic_name_when_the_slug_is_empty(): void
    {
        // Arrange

        $data = $this->resolveArguments(
            folder: null,
            name: '!!!',
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/request.request.json",
            $filepath,
        );
    }

    public function test_it_falls_back_to_a_generic_name_when_there_is_no_name_and_no_current_file(): void
    {
        // Arrange

        $data = $this->resolveArguments(
            folder: null,
            name: null,
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/request.request.json",
            $filepath,
        );
    }

    public function test_it_limits_the_length_of_the_slug(): void
    {
        // Arrange

        $data = $this->resolveArguments(
            folder: null,
            name: str_repeat('a', 200),
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $expected = str_repeat('a', 80);

        $this->assertSame(
            "{$this->workspacePath}/requests/{$expected}.request.json",
            $filepath,
        );
    }

    public function test_it_adds_a_number_when_the_filepath_is_taken(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(id: 'request-0001'),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(id: 'request-0002'),
            hash: null,
        );

        $data = $this->resolveArguments(
            folder: null,
            name: 'Create user',
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/create-user-3.request.json",
            $filepath,
        );
    }

    public function test_it_counts_the_current_file_as_free(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $current = $this->findRequest('request-1234');

        $data = $this->resolveArguments(
            folder: null,
            name: 'Create user',
            current: $current,
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            $current->filepath,
            $filepath,
        );
    }

    public function test_it_keeps_the_folder_and_the_name_of_the_current_file(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        $current = $this->findRequest('request-1234');

        $data = $this->resolveArguments(
            folder: null,
            name: null,
            current: $current,
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            $current->filepath,
            $filepath,
        );
    }

    public function test_it_moves_the_current_file_to_another_folder_and_keeps_its_name(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        $current = $this->findRequest('request-1234');

        $data = $this->resolveArguments(
            folder: 'archive',
            name: null,
            current: $current,
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/archive/create-user.request.json",
            $filepath,
        );
    }

    public function test_it_renames_the_current_file_and_keeps_its_folder(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        $current = $this->findRequest('request-1234');

        $data = $this->resolveArguments(
            folder: null,
            name: 'Register user',
            current: $current,
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/users/register-user.request.json",
            $filepath,
        );
    }

    public function test_it_moves_the_current_file_to_the_root(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: 'users',
        );

        $current = $this->findRequest('request-1234');

        $data = $this->resolveArguments(
            folder: '',
            name: null,
            current: $current,
        );

        $action = resolve(ResolveRequestFilepathAction::class);

        // Act

        $filepath = $action->execute(...$data);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/requests/create-user.request.json",
            $filepath,
        );
    }

    private function resolveArguments(?string $folder, ?string $name, ?RequestFile $current = null): array
    {
        $folderPath = $folder === null ? null : FolderPath::from($folder);

        return [
            'folder' => $folderPath,
            'name' => $name,
            'current' => $current,
        ];
    }
}
