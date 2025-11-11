<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services\Uri;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Services\Uri\NonVersionedUri;

#[CoversClass(NonVersionedUri::class)]
class NonVersionedUriUnitTest extends TestCase
{
    #[TestWith(['/users'])]
    #[TestWith(['/v1/users'])]
    public function test_it_always_returns_na_for_version(string $value): void
    {
        $uri = new NonVersionedUri(value: $value, routesPrefix: '');

        $this->assertEquals('n/a', $uri->getVersion());
    }

    #[DataProvider('resourceExtractionProvider')]
    public function test_it_extracts_resource_correctly(
        string $value,
        string $routesPrefix,
        string $expectedResource
    ): void {
        $uri = new NonVersionedUri($value, $routesPrefix);

        $this->assertEquals($expectedResource, $uri->getResource());
    }

    public static function resourceExtractionProvider(): Generator
    {
        yield 'simple uri' => [
            'value' => '/users',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'uri with multiple segments' => [
            'value' => '/users/123/profile',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'uri with routesPrefix' => [
            'value' => '/api/users',
            'routesPrefix' => 'api',
            'expectedResource' => 'users',
        ];

        yield 'uri with composed routesPrefix' => [
            'value' => '/web/api/users',
            'routesPrefix' => 'web/api',
            'expectedResource' => 'users',
        ];

        yield 'uri with routesPrefix and multiple segments' => [
            'value' => '/api/users/123',
            'routesPrefix' => 'api',
            'expectedResource' => 'users',
        ];

        yield 'routesPrefix not at start' => [
            'value' => '/users/api/123',
            'routesPrefix' => 'api',
            'expectedResource' => 'users', // <- /api/.. is considered part of the URI and disregarded the prefix.
        ];

        yield 'empty uri' => [
            'value' => '',
            'routesPrefix' => '',
            'expectedResource' => '',
        ];

        yield 'only slashes' => [
            'value' => '///',
            'routesPrefix' => '',
            'expectedResource' => '',
        ];

        yield 'only routesPrefix' => [
            'value' => '/api',
            'routesPrefix' => 'api',
            'expectedResource' => '',
        ];

        yield 'only routesPrefix with trailing slash' => [
            'value' => '/api/',
            'routesPrefix' => 'api',
            'expectedResource' => '',
        ];

        yield 'empty routesPrefix' => [
            'value' => '/users',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'uri without leading slash' => [
            'value' => 'users/123',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'uri with trailing slash' => [
            'value' => '/users/',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'multiple consecutive slashes' => [
            'value' => '//users//123//',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'routesPrefix without leading slash in uri' => [
            'value' => 'api/users',
            'routesPrefix' => 'api',
            'expectedResource' => 'users',
        ];

        yield 'single character resource' => [
            'value' => '/a',
            'routesPrefix' => '',
            'expectedResource' => 'a',
        ];

        yield 'numeric resource' => [
            'value' => '/123',
            'routesPrefix' => '',
            'expectedResource' => '123',
        ];

        yield 'resource with special characters' => [
            'value' => '/user-profile',
            'routesPrefix' => '',
            'expectedResource' => 'user-profile',
        ];

        yield 'case sensitive routesPrefix mismatch' => [
            'value' => '/API/users',
            'routesPrefix' => 'api',
            'expectedResource' => 'API',
        ];

        yield 'routesPrefix as part of resource name' => [
            'value' => '/api/api-users',
            'routesPrefix' => 'api',
            'expectedResource' => 'api-users',
        ];

        yield 'deeply nested uri' => [
            'value' => '/api/v1/users/123/posts/456/comments',
            'routesPrefix' => 'api',
            'expectedResource' => 'v1', // <- remember: we are testing the `NonVersionedUri`
        ];

        yield 'whitespace in uri' => [
            'value' => '/ users /123',
            'routesPrefix' => '',
            'expectedResource' => ' users ',
        ];

        yield 'url encoded characters' => [
            'value' => '/user%20name',
            'routesPrefix' => '',
            'expectedResource' => 'user%20name',
        ];
    }

    public function test_it_can_call_get_resource_multiple_times(): void
    {
        $uri = new NonVersionedUri('/users', '');

        // This asserts against mutating the original value.
        $this->assertEquals('users', $uri->getResource());
        $this->assertEquals('users', $uri->getResource());
    }
}
