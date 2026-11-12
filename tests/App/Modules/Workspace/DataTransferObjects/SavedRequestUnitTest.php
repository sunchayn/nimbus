<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\DataTransferObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

#[CoversClass(SavedRequest::class)]
class SavedRequestUnitTest extends TestCase
{
    public function test_it_writes_the_keys_in_a_stable_order(): void
    {
        // Arrange

        $request = SavedRequest::fromArray([
            'authorization' => ['type' => 'bearer', 'value' => '{{token}}'],
            'endpoint' => '/api/users',
            'method' => 'post',
            'name' => 'Create user',
            'id' => 'request-1',
            'route' => 'users.store',
            'body' => ['type' => 'json', 'content' => '{}'],
        ]);

        // Act

        $data = $request->toArray();

        // Assert

        $this->assertSame(
            ['id', 'name', 'method', 'endpoint', 'route', 'headers', 'query', 'body', 'authorization'],
            array_keys($data),
        );

        $this->assertSame(
            'POST',
            $data['method'],
        );
    }

    public function test_it_omits_an_unknown_route_and_an_empty_body_content(): void
    {
        // Arrange

        $request = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'List users',
            'method' => 'GET',
            'endpoint' => '/api/users',
            'body' => ['type' => 'empty', 'content' => 'leftover'],
            'authorization' => ['type' => 'none', 'value' => 'leftover'],
        ]);

        // Act

        $data = $request->toArray();

        // Assert

        $this->assertArrayNotHasKey(
            'route',
            $data,
        );

        $this->assertSame(
            ['type' => 'empty'],
            $data['body'],
        );

        $this->assertSame(
            ['type' => 'none'],
            $data['authorization'],
        );
    }

    public function test_it_round_trips_through_an_array(): void
    {
        // Arrange

        $original = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'Login',
            'method' => 'POST',
            'endpoint' => '/api/login',
            'headers' => [['key' => 'Accept', 'value' => 'application/json', 'enabled' => false]],
            'query' => [['key' => 'page', 'value' => '1', 'enabled' => true]],
            'body' => ['type' => 'plain-text', 'content' => 'hello'],
            'authorization' => ['type' => 'basic', 'value' => ['username' => 'admin', 'password' => '{{password}}']],
        ]);

        // Act

        $restored = SavedRequest::fromArray($original->toArray());

        // Assert

        $this->assertEquals(
            $original,
            $restored,
        );
    }

    public function test_it_turns_into_the_text_of_a_file(): void
    {
        // Arrange

        $request = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'Création',
            'method' => 'GET',
            'endpoint' => '{{base_url}}/api/users',
        ]);

        // Act

        $json = $request->toJson();

        // Assert

        $this->assertSame(
            $json,
            (string) $request,
        );

        $this->assertStringEndsWith(
            "}\n",
            $json,
        );

        $this->assertStringContainsString(
            "\n  \"name\": \"Création\",\n",
            $json,
        );

        $this->assertStringContainsString(
            '{{base_url}}/api/users',
            $json,
        );
    }

    public function test_it_reads_the_text_of_a_file(): void
    {
        // Arrange

        $request = SavedRequest::fromArray(['id' => 'request-1', 'name' => 'Create user', 'method' => 'post']);

        // Act

        $read = SavedRequest::fromRaw($request->toJson());

        // Assert

        $this->assertSame(
            $request->toArray(),
            $read->toArray(),
        );
    }

    public function test_it_changes_only_the_name(): void
    {
        // Arrange

        $request = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'Create user',
            'method' => 'post',
            'authorization' => ['type' => 'bearer', 'value' => '{{token}}'],
            'body' => ['type' => 'json', 'content' => '{}'],
        ]);

        // Act

        $renamed = $request->withName('Create admin');

        // Assert

        $this->assertSame(
            'Create admin',
            $renamed->name,
        );

        $this->assertSame(
            'Create user',
            $request->name,
        );

        $this->assertSame(
            [...$request->toArray(), 'name' => 'Create admin'],
            $renamed->toArray(),
        );
    }

    public function test_it_cleans_the_headers_and_the_query(): void
    {
        // Arrange

        $request = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'List users',
            'headers' => ['not a parameter', ['key' => 'Accept', 'value' => 'application/json']],
            'query' => 'not a list',
        ]);

        // Act

        $data = $request->toArray();

        // Assert

        $this->assertSame(
            [['key' => 'Accept', 'value' => 'application/json', 'enabled' => true]],
            $data['headers'],
        );

        $this->assertSame(
            [],
            $data['query'],
        );
    }

    public function test_it_falls_back_to_no_authorization_for_an_unknown_type(): void
    {
        // Arrange

        $request = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'List users',
            'authorization' => ['type' => 'unknown', 'value' => 'secret'],
        ]);

        // Act

        $data = $request->toArray();

        // Assert

        $this->assertSame(
            ['type' => 'none'],
            $data['authorization'],
        );
    }

    #[DataProvider('unreadableRequests')]
    public function test_it_refuses_text_that_is_not_a_request(string $json): void
    {
        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('not a valid request');

        // Act

        SavedRequest::fromRaw($json);
    }

    /**
     * @return Generator<string, array{json: string}>
     */
    public static function unreadableRequests(): Generator
    {
        yield 'invalid json' => [
            'json' => '{ nope',
        ];

        yield 'a json text' => [
            'json' => '"text"',
        ];

        yield 'no id' => [
            'json' => '{"name": "No id"}',
        ];

        yield 'no name' => [
            'json' => '{"id": "request-1"}',
        ];

        yield 'an id that is not a string' => [
            'json' => '{"id": 1, "name": "Numeric id"}',
        ];
    }
}
