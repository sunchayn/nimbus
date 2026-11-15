<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\ReorderWorkspaceFoldersController;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\ReorderWorkspaceFoldersRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReorderWorkspaceFoldersAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ReorderWorkspaceFoldersController::class)]
#[CoversClass(ReorderWorkspaceFoldersRequest::class)]
class ReorderWorkspaceFoldersTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_passes_the_folders_to_the_action_in_order(): void
    {
        // Arrange

        $actionSpy = $this->spy(ReorderWorkspaceFoldersAction::class);

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.folders.order.update'),
            [
                'folders' => ['Billing', 'Users/Admin'],
            ],
        );

        // Assert

        $response->assertNoContent();

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (array $folders): bool {
                $this->assertSame(
                    ['billing', 'users/admin'],
                    array_map(strval(...), $folders),
                );

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

        $actionSpy = $this->spy(ReorderWorkspaceFoldersAction::class);

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.folders.order.update'),
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
        yield 'folders are missing' => [
            'payload' => [],
            'field' => 'folders',
        ];

        yield 'folders are not a list' => [
            'payload' => ['folders' => 'users'],
            'field' => 'folders',
        ];

        yield 'folders are too many' => [
            'payload' => ['folders' => array_fill(0, 501, 'users')],
            'field' => 'folders',
        ];

        yield 'a folder has no letter or number' => [
            'payload' => ['folders' => ['users', '!!!']],
            'field' => 'folders.1',
        ];

        yield 'a folder is nested too deep' => [
            'payload' => ['folders' => ['a/b/c/d/e/f']],
            'field' => 'folders.0',
        ];
    }

    public function test_it_reports_a_folder_that_does_not_exist(): void
    {
        // Arrange

        $actionSpy = $this->spy(ReorderWorkspaceFoldersAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::folderNotFound('billing'));

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.folders.order.update'),
            ['folders' => ['billing']],
        );

        // Assert

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'The folder `billing` does not exist in the workspace.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        $this->createFolder('alpha');

        $this->createFolder('bravo');

        // Act

        $response = $this->putJson(
            route('nimbus.api.workspace.folders.order.update'),
            [
                'folders' => ['bravo', 'alpha'],
            ],
        );

        // Assert

        $response->assertNoContent();

        $this->assertSame(
            ['bravo', 'alpha'],
            $this->listFolders(),
        );
    }
}
