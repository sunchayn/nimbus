<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\DataTransferObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

#[CoversClass(ExtractedRoute::class)]
class ExtractedRouteUnitTest extends TestCase
{
    public function test_it_generates_signatures_for_simple_uri(): void
    {
        // Arrange

        $route = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        // Act

        $signatures = $route->getRouteSignatures();

        // Assert

        $this->assertEquals(['GET' => 'GET@api/users'], $signatures);
    }

    public function test_it_generates_signatures_for_multiple_methods(): void
    {
        // Arrange

        $route = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET', 'POST'],
            schema: Schema::empty(),
        );

        // Act

        $signatures = $route->getRouteSignatures();

        // Assert

        $this->assertEquals([
            'GET' => 'GET@api/users',
            'POST' => 'POST@api/users',
        ], $signatures);
    }

    #[DataProvider('parameterizedUriDataProvider')]
    public function test_it_normalizes_parameters_in_signatures(string $uri, string $expectedSignatureSuffix): void
    {
        // Arrange

        $route = new ExtractedRoute(
            uri: Endpoint::fromRaw($uri, 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        // Act

        $signatures = $route->getRouteSignatures();

        // Assert

        $this->assertEquals(['GET' => 'GET@'.$expectedSignatureSuffix], $signatures);
    }

    public static function parameterizedUriDataProvider(): Generator
    {
        yield 'single parameter' => [
            'uri' => 'api/users/{id}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'multiple parameters' => [
            'uri' => 'api/posts/{post}/comments/{comment}',
            'expectedSignatureSuffix' => 'api/posts/{}/comments/{}',
        ];

        yield 'parameter with underscore' => [
            'uri' => 'api/users/{user_id}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'leading slash is trimmed' => [
            'uri' => '/api/users/{id}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'trailing slash is trimmed' => [
            'uri' => 'api/users/{id}/',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'optional parameter treated as regular' => [
            'uri' => 'api/users/{id?}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];
    }
}
