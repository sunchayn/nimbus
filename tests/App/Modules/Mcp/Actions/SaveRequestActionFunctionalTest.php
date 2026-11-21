<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Mcp\Actions\SaveRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SaveRequestData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SaveRequestAction::class)]
class SaveRequestActionFunctionalTest extends TestCase
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

    public function test_it_saves_a_request_in_its_folder(): void
    {
        // Arrange

        $data = new SaveRequestData(
            name: 'Create user',
            method: 'post',
            endpoint: '/api/users',
            folder: 'users',
        );

        $action = resolve(SaveRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            'requests/users/create-user.request.json',
            $result['file'],
        );

        $this->assertFileExists("{$this->workspacePath}/requests/users/create-user.request.json");

        $this->assertSame(
            [$result['id']],
            $this->listRequestIds(),
        );
    }

    public function test_it_saves_the_method_in_upper_case(): void
    {
        // Arrange

        $data = new SaveRequestData(
            name: 'Create user',
            method: 'post',
            endpoint: '/api/users',
        );

        $action = resolve(SaveRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            'POST',
            $this->findRequest($result['id'])->savedRequest->method,
        );
    }

    public function test_it_saves_the_headers_and_the_query_as_enabled_parameters(): void
    {
        // Arrange

        $data = new SaveRequestData(
            name: 'Search users',
            method: 'GET',
            endpoint: '/api/users',
            headers: ['Accept' => 'application/json'],
            query: ['page' => 2],
        );

        $action = resolve(SaveRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $savedRequest = $this->findRequest($result['id'])->savedRequest;

        $this->assertSame(
            [['key' => 'Accept', 'value' => 'application/json', 'enabled' => true]],
            $savedRequest->headers,
        );

        $this->assertSame(
            [['key' => 'page', 'value' => '2', 'enabled' => true]],
            $savedRequest->query,
        );
    }

    public function test_it_saves_an_array_body_as_json(): void
    {
        // Arrange

        $data = new SaveRequestData(
            name: 'Create user',
            method: 'POST',
            endpoint: '/api/users',
            body: ['name' => '{{name}}'],
        );

        $action = resolve(SaveRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $savedRequest = $this->findRequest($result['id'])->savedRequest;

        $this->assertSame(
            'json',
            $savedRequest->bodyType,
        );

        $this->assertSame(
            ['name' => '{{name}}'],
            json_decode($savedRequest->bodyContent, true),
        );
    }

    public function test_it_saves_a_string_body_as_plain_text(): void
    {
        // Arrange

        $data = new SaveRequestData(
            name: 'Create note',
            method: 'POST',
            endpoint: '/api/notes',
            body: 'hello',
        );

        $action = resolve(SaveRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $savedRequest = $this->findRequest($result['id'])->savedRequest;

        $this->assertSame(
            'plain-text',
            $savedRequest->bodyType,
        );

        $this->assertSame(
            'hello',
            $savedRequest->bodyContent,
        );
    }

    public function test_it_saves_an_empty_body_as_empty(): void
    {
        // Arrange

        $data = new SaveRequestData(
            name: 'List users',
            method: 'GET',
            endpoint: '/api/users',
        );

        $action = resolve(SaveRequestAction::class);

        // Act

        $result = $action->execute($data);

        // Assert

        $this->assertSame(
            'empty',
            $this->findRequest($result['id'])->savedRequest->bodyType,
        );
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('invalidRequests')]
    public function test_it_rejects_an_invalid_request(array $arguments, string $field): void
    {
        // Arrange

        $data = new SaveRequestData(...[
            'name' => 'Create user',
            'method' => 'POST',
            'endpoint' => '/api/users',
            ...$arguments,
        ]);

        $action = resolve(SaveRequestAction::class);

        // Act

        $exception = null;

        try {
            $action->execute($data);
        } catch (McpToolException $mcpToolException) {
            $exception = $mcpToolException;
        }

        // Assert

        $this->assertInstanceOf(
            McpToolException::class,
            $exception,
        );

        $this->assertSame(
            McpToolException::INVALID_INPUT,
            $exception->errorCode,
            "The `{$field}` must be rejected.",
        );

        $this->assertSame(
            [],
            $this->listRequestIds(),
        );
    }

    public static function invalidRequests(): Generator
    {
        yield 'name is blank' => [
            'arguments' => ['name' => '  '],
            'field' => 'name',
        ];

        yield 'method is unknown' => [
            'arguments' => ['method' => 'FETCH'],
            'field' => 'method',
        ];

        yield 'endpoint is empty' => [
            'arguments' => ['endpoint' => ''],
            'field' => 'endpoint',
        ];

        yield 'authorization header is set' => [
            'arguments' => ['headers' => ['Authorization' => 'Bearer abc']],
            'field' => 'headers',
        ];

        yield 'cookie header is set in another case' => [
            'arguments' => ['headers' => ['COOKIE' => 'a=b']],
            'field' => 'headers',
        ];

        yield 'api key header is set' => [
            'arguments' => ['headers' => ['X-Api-Key' => 'secret']],
            'field' => 'headers',
        ];
    }
}
