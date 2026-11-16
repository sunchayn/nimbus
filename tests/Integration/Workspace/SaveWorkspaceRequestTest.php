<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Workspace;

use Generator;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Workspace\Controllers\SaveWorkspaceRequestController;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\SaveWorkspaceRequestRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\SaveWorkspaceRequestAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SaveWorkspaceRequestController::class)]
#[CoversClass(SaveWorkspaceRequestRequest::class)]
class SaveWorkspaceRequestTest extends TestCase
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

        $savedRequest = $this->savedRequest();

        $actionSpy = $this->spy(SaveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                $this->requestFile($savedRequest),
            );

        // Act

        $this->putJson(
            route(
                'nimbus.api.workspace.requests.update',
                'request-1234',
            ),
            $this->requestPayload(['hash' => 'a1b2c3d4e5f6a7b8', 'folder' => 'users']),
        );

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SavedRequest $savedRequest, ?string $hash, FolderPath $folder): bool {
                $this->assertSame(
                    'request-1234',
                    $savedRequest->id,
                );

                $this->assertSame(
                    'Create user',
                    $savedRequest->name,
                );

                $this->assertSame(
                    'POST',
                    $savedRequest->method,
                );

                $this->assertSame(
                    '{{base_url}}/api/users',
                    $savedRequest->endpoint,
                );

                $this->assertSame(
                    'users.store',
                    $savedRequest->route,
                );

                $this->assertSame(
                    'json',
                    $savedRequest->bodyType,
                );

                $this->assertSame(
                    '{{token}}',
                    $savedRequest->authorizationValue,
                );

                $this->assertSame(
                    'a1b2c3d4e5f6a7b8',
                    $hash,
                );

                $this->assertSame(
                    'users',
                    (string) $folder,
                );

                return true;
            });
    }

    public function test_it_defaults_to_a_new_request_in_the_root_folder(): void
    {
        // Arrange

        $actionSpy = $this->spy(SaveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                $this->requestFile($this->savedRequest()),
            );

        // Act

        $this->putJson(
            route(
                'nimbus.api.workspace.requests.update',
                'request-1234',
            ),
            Arr::except(
                $this->requestPayload(),
                ['hash', 'folder'],
            ),
        );

        // Assert

        $actionSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (SavedRequest $savedRequest, ?string $hash, FolderPath $folder): bool {
                $this->assertNull($hash);
                $this->assertTrue($folder->isRoot());

                return true;
            });
    }

    public function test_it_returns_the_saved_request_and_its_hash(): void
    {
        // Arrange

        $requestFile = $this->requestFile(
            $this->savedRequest(),
            hash: 'a1b2c3d4e5f6a7b8',
        );

        $actionSpy = $this->spy(SaveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andReturn(
                $requestFile,
            );

        // Act

        $response = $this->putJson(
            route(
                'nimbus.api.workspace.requests.update',
                'request-1234',
            ),
            $this->requestPayload(),
        );

        // Assert

        $response
            ->assertOk()
            ->assertExactJson($requestFile->toArray());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<string>  $omit
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_an_invalid_payload(array $overrides, array $omit, string $field): void
    {
        // Arrange

        $actionSpy = $this->spy(SaveWorkspaceRequestAction::class);

        $payload = Arr::except(
            $this->requestPayload($overrides),
            $omit,
        );

        // Act

        $response = $this->putJson(
            route(
                'nimbus.api.workspace.requests.update',
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
        yield 'name is missing' => [
            'overrides' => [],
            'omit' => ['name'],
            'field' => 'name',
        ];

        yield 'name is too long' => [
            'overrides' => ['name' => str_repeat('a', 121)],
            'omit' => [],
            'field' => 'name',
        ];

        yield 'method is missing' => [
            'overrides' => [],
            'omit' => ['method'],
            'field' => 'method',
        ];

        yield 'method is not supported' => [
            'overrides' => ['method' => 'TRACE'],
            'omit' => [],
            'field' => 'method',
        ];

        yield 'endpoint is missing' => [
            'overrides' => [],
            'omit' => ['endpoint'],
            'field' => 'endpoint',
        ];

        yield 'endpoint is too long' => [
            'overrides' => ['endpoint' => str_repeat('a', 2049)],
            'omit' => [],
            'field' => 'endpoint',
        ];

        yield 'headers are missing' => [
            'overrides' => [],
            'omit' => ['headers'],
            'field' => 'headers',
        ];

        yield 'a header has no enabled flag' => [
            'overrides' => ['headers' => [['key' => 'Accept', 'value' => 'application/json']]],
            'omit' => [],
            'field' => 'headers.0.enabled',
        ];

        yield 'query is missing' => [
            'overrides' => [],
            'omit' => ['query'],
            'field' => 'query',
        ];

        yield 'a query parameter has no enabled flag' => [
            'overrides' => ['query' => [['key' => 'page', 'value' => '1']]],
            'omit' => [],
            'field' => 'query.0.enabled',
        ];

        yield 'body is missing' => [
            'overrides' => [],
            'omit' => ['body'],
            'field' => 'body',
        ];

        yield 'body type is not supported' => [
            'overrides' => ['body' => ['type' => 'form-data', 'content' => '']],
            'omit' => [],
            'field' => 'body.type',
        ];

        yield 'authorization is missing' => [
            'overrides' => [],
            'omit' => ['authorization'],
            'field' => 'authorization',
        ];

        yield 'authorization type is unknown' => [
            'overrides' => ['authorization' => ['type' => 'magic', 'value' => 'x']],
            'omit' => [],
            'field' => 'authorization.type',
        ];

        yield 'hash is too long' => [
            'overrides' => ['hash' => str_repeat('a', 65)],
            'omit' => [],
            'field' => 'hash',
        ];

        yield 'folder has no letter or number' => [
            'overrides' => ['folder' => '!!!'],
            'omit' => [],
            'field' => 'folder',
        ];

        yield 'folder is nested too deep' => [
            'overrides' => ['folder' => 'a/b/c/d/e/f'],
            'omit' => [],
            'field' => 'folder',
        ];
    }

    public function test_it_reports_a_conflict(): void
    {
        // Arrange

        $actionSpy = $this->spy(SaveWorkspaceRequestAction::class);

        // Anticipate

        $actionSpy
            ->shouldReceive('execute')
            ->andThrow(WorkspaceException::conflict());

        // Act

        $response = $this->putJson(
            route(
                'nimbus.api.workspace.requests.update',
                'request-1234',
            ),
            $this->requestPayload(),
        );

        // Assert

        $response
            ->assertStatus(409)
            ->assertExactJson([
                'message' => 'The request file changed on disk since it was loaded.',
            ]);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->enableWorkspace();

        // Act

        $response = $this->putJson(
            route(
                'nimbus.api.workspace.requests.update',
                'request-1234',
            ),
            $this->requestPayload(['folder' => 'users']),
        );

        // Assert

        $response->assertOk();

        $this->assertSame(
            'request-1234',
            $response->json('request.id'),
        );

        $this->assertFileExists("{$this->workspacePath}/requests/users/create-user.request.json");

        $this->assertSame(
            ['request-1234'],
            $this->listRequestIds(),
        );
    }
}
