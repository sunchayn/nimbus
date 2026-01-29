<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\ValueObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;

#[CoversClass(Endpoint::class)]
class EndpointUnitTest extends TestCase
{
    #[DataProvider('versionedEndpointProvider')]
    public function test_it_creates_versioned_endpoint_from_raw(
        string $uri,
        string $routesPrefix,
        string $expectedVersion,
        string $expectedResource,
    ): void {
        // Act

        $endpoint = Endpoint::fromRaw(
            uri: $uri,
            routesPrefix: $routesPrefix,
            isVersioned: true
        );

        // Assert

        $this->assertEquals($expectedVersion, $endpoint->version);
        $this->assertEquals($expectedResource, $endpoint->resource);
        $this->assertEquals($uri, $endpoint->value);
    }

    public static function versionedEndpointProvider(): Generator
    {
        yield 'simple uri' => [
            'uri' => '/v1/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
            'expectedResource' => 'users',
        ];

        yield 'uri with semantic version' => [
            'uri' => '/1.0/posts',
            'routesPrefix' => '',
            'expectedVersion' => '1.0',
            'expectedResource' => 'posts',
        ];

        yield 'uri with prefix' => [
            'uri' => '/api/v1/users',
            'routesPrefix' => 'api',
            'expectedVersion' => 'v1',
            'expectedResource' => 'users',
        ];

        yield 'uri with prefix and semantic version' => [
            'uri' => '/rest-api/2.0/products',
            'routesPrefix' => 'rest-api',
            'expectedVersion' => '2.0',
            'expectedResource' => 'products',
        ];

        yield 'uri with multiple segments' => [
            'uri' => '/v1/users/{user}/posts',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
            'expectedResource' => 'users',
        ];

        yield 'uri with prefix and path parameters' => [
            'uri' => '/api/v2/users/{id}',
            'routesPrefix' => 'api',
            'expectedVersion' => 'v2',
            'expectedResource' => 'users',
        ];

        yield 'empty uri' => [
            'uri' => '',
            'routesPrefix' => '',
            'expectedVersion' => 'v1', // <- Default version.
            'expectedResource' => '',
        ];
    }

    #[DataProvider('nonVersionedEndpointProvider')]
    public function test_it_creates_non_versioned_endpoint_from_raw(
        string $uri,
        string $routesPrefix,
        string $expectedVersion,
        string $expectedResource,
    ): void {
        // Act

        $endpoint = Endpoint::fromRaw(
            uri: $uri,
            routesPrefix: $routesPrefix,
            isVersioned: false
        );

        // Assert

        $this->assertEquals($expectedVersion, $endpoint->version);
        $this->assertEquals($expectedResource, $endpoint->resource);
        $this->assertEquals($uri, $endpoint->value);
    }

    public static function nonVersionedEndpointProvider(): Generator
    {
        yield 'simple uri' => [
            'uri' => '/users',
            'routesPrefix' => '',
            'expectedVersion' => 'n/a',
            'expectedResource' => 'users',
        ];

        yield 'uri with prefix' => [
            'uri' => '/api/users',
            'routesPrefix' => 'api',
            'expectedVersion' => 'n/a',
            'expectedResource' => 'users',
        ];

        yield 'uri with multiple segments' => [
            'uri' => '/users/{user}/posts',
            'routesPrefix' => '',
            'expectedVersion' => 'n/a',
            'expectedResource' => 'users',
        ];

        yield 'uri with prefix and path parameters' => [
            'uri' => '/api/posts/{id}',
            'routesPrefix' => 'api',
            'expectedVersion' => 'n/a',
            'expectedResource' => 'posts',
        ];

        yield 'empty uri' => [
            'uri' => '',
            'routesPrefix' => '',
            'expectedVersion' => 'n/a',
            'expectedResource' => '',
        ];
    }

    #[DataProvider('shortUriProvider')]
    public function test_it_returns_short_uri_correctly(
        string $version,
        string $resource,
        string $value,
        string $expectedShortUri,
        ?string $shortUriOverride = null,
    ): void {
        // Arrange

        $endpoint = new Endpoint(
            version: $version,
            resource: $resource,
            value: $value,
            shortUriOverride: $shortUriOverride,
        );

        // Act & Assert

        $this->assertEquals($expectedShortUri, $endpoint->getShortUri());
    }

    public static function shortUriProvider(): Generator
    {
        yield 'versioned uri with prefix removes prefix and version' => [
            'version' => 'v1',
            'resource' => 'users',
            'value' => '/rest-api/v1/users/{user}',
            'expectedShortUri' => '/users/{user}',
        ];

        yield 'versioned uri without prefix removes only version' => [
            'version' => 'v1',
            'resource' => 'users',
            'value' => '/v1/users/{user}',
            'expectedShortUri' => '/users/{user}',
        ];

        yield 'uri with prefix removes only prefix' => [
            'version' => 'n/a',
            'resource' => 'users',
            'value' => '/api/users/{user}',
            'expectedShortUri' => '/users/{user}',
        ];

        yield 'uri without prefix returns as is' => [
            'version' => 'n/a',
            'resource' => 'users',
            'value' => '/users/{user}',
            'expectedShortUri' => '/users/{user}',
        ];

        yield 'simple resource without parameters' => [
            'version' => 'v1',
            'resource' => 'users',
            'value' => '/api/v1/users',
            'expectedShortUri' => '/users',
        ];

        yield 'deeply nested uri' => [
            'version' => 'v1',
            'resource' => 'users',
            'value' => '/api/v1/users/{user}/posts/{post}/comments',
            'expectedShortUri' => '/users/{user}/posts/{post}/comments',
        ];

        yield 'uri with semantic version' => [
            'version' => '1.0',
            'resource' => 'products',
            'value' => '/api/1.0/products/{id}',
            'expectedShortUri' => '/products/{id}',
        ];

        yield 'uri with shortUriOverride returns the override' => [
            'version' => 'v1',
            'resource' => 'users',
            'value' => '/api/v1/users',
            'expectedShortUri' => 'listUsers',
            'shortUriOverride' => 'listUsers',
        ];
    }

    #[DataProvider('invalidShortUriProvider')]
    public function test_it_throws_exception_for_invalid_short_uri(
        string $version,
        string $resource,
        string $value,
        string $expectedMessage,
    ): void {
        // Arrange

        $endpoint = new Endpoint(
            version: $version,
            resource: $resource,
            value: $value,
        );

        // Anticipate

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Act

        $endpoint->getShortUri();
    }

    public static function invalidShortUriProvider(): Generator
    {
        yield 'empty resource' => [
            'version' => 'v1',
            'resource' => '',
            'value' => '/v1/users',
            'expectedMessage' => 'Invalid ValueObject. The resource cannot be empty.',
        ];

        yield 'resource not in uri' => [
            'version' => 'v1',
            'resource' => 'posts',
            'value' => '/v1/users/{user}',
            'expectedMessage' => 'Invalid ValueObject. The `resource` MUST exist in the URI.',
        ];

        yield 'resource with different casing' => [
            'version' => 'v1',
            'resource' => 'Users',
            'value' => '/v1/users',
            'expectedMessage' => 'Invalid ValueObject. The `resource` MUST exist in the URI.',
        ];

        yield 'resource without leading slash in uri' => [
            'version' => 'n/a',
            'resource' => 'users',
            'value' => 'users/{user}',
            'expectedMessage' => 'Invalid ValueObject. The `resource` MUST exist in the URI.',
        ];

        yield 'resource appears later in path' => [
            'version' => 'v1',
            'resource' => 'comments',
            'value' => '/v1/users/{user}/posts',
            'expectedMessage' => 'Invalid ValueObject. The `resource` MUST exist in the URI.',
        ];
    }
}
