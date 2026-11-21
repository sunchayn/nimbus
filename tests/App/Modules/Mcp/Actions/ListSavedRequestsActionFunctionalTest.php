<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListSavedRequestsAction;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListSavedRequestsAction::class)]
class ListSavedRequestsActionFunctionalTest extends TestCase
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

    public function test_it_lists_the_requests_with_their_summary(): void
    {
        // Arrange

        $this->saveRequest(
            savedRequest: $this->savedRequest(id: 'request-1', name: 'Create user'),
            hash: null,
            folder: 'users',
        );

        $action = resolve(ListSavedRequestsAction::class);

        // Act

        $result = $action->execute();

        // Assert

        $this->assertSame(
            1,
            $result['count'],
        );

        $this->assertSame(
            [
                'id' => 'request-1',
                'name' => 'Create user',
                'method' => 'POST',
                'endpoint' => '{{base_url}}/api/users',
                'route' => 'users.store',
                'file' => 'requests/users/create-user.request.json',
                'folder' => 'users',
            ],
            $result['requests'][0],
        );
    }

    public function test_it_lists_the_folders_of_the_workspace(): void
    {
        // Arrange

        $this->createFolder('users/admin');

        $this->createFolder('posts');

        $action = resolve(ListSavedRequestsAction::class);

        // Act

        $result = $action->execute();

        // Assert

        $this->assertSame(
            ['users', 'users/admin', 'posts'],
            $result['folders'],
        );

        $this->assertSame(
            0,
            $result['count'],
        );
    }

    public function test_it_filters_the_requests_by_folder(): void
    {
        // Arrange

        $this->saveRequestInFolder(
            'request-1',
            'Create user',
            'users',
        );

        $this->saveRequestInFolder(
            'request-2',
            'Create admin',
            'users/admin',
        );

        $this->saveRequestInFolder(
            'request-3',
            'Create post',
            'posts',
        );

        $action = resolve(ListSavedRequestsAction::class);

        // Act

        $result = $action->execute('users');

        // Assert

        $this->assertSame(
            ['request-1', 'request-2'],
            array_column($result['requests'], 'id'),
        );

        $this->assertSame(
            2,
            $result['count'],
        );
    }

    public function test_it_does_not_match_a_folder_by_its_prefix_only(): void
    {
        // Arrange

        $this->saveRequestInFolder(
            'request-1',
            'Create user',
            'users',
        );

        $this->saveRequestInFolder(
            'request-2',
            'Create other',
            'users-archive',
        );

        $action = resolve(ListSavedRequestsAction::class);

        // Act

        $result = $action->execute('/users/');

        // Assert

        $this->assertSame(
            ['request-1'],
            array_column($result['requests'], 'id'),
        );
    }

    public function test_it_returns_an_empty_list_for_an_unknown_folder(): void
    {
        // Arrange

        $this->saveRequestInFolder(
            'request-1',
            'Create user',
            'users',
        );

        $action = resolve(ListSavedRequestsAction::class);

        // Act

        $result = $action->execute('other');

        // Assert

        $this->assertSame(
            0,
            $result['count'],
        );

        $this->assertSame(
            [],
            $result['requests'],
        );
    }

    /*
     * Seeders.
     */

    private function saveRequestInFolder(string $id, string $name, string $folder): void
    {
        $this->saveRequest(
            savedRequest: $this->savedRequest(id: $id, name: $name),
            hash: null,
            folder: $folder,
        );
    }
}
