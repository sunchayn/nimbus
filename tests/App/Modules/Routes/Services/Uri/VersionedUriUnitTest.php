<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services\Uri;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Services\Uri\VersionedUri;

#[CoversClass(VersionedUri::class)]
class VersionedUriUnitTest extends TestCase
{
    #[DataProvider('versionExtractionProvider')]
    public function test_it_extracts_version_correctly(
        string $value,
        string $routesPrefix,
        string $expectedVersion
    ): void {
        $uri = new VersionedUri(value: $value, routesPrefix: $routesPrefix);

        $this->assertEquals($expectedVersion, $uri->getVersion());
    }

    public static function versionExtractionProvider(): Generator
    {
        yield 'simple versioned uri with v prefix' => [
            'value' => '/v1/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'versioned uri with numeric version' => [
            'value' => '/v2/posts',
            'routesPrefix' => '',
            'expectedVersion' => 'v2',
        ];

        yield 'versioned uri with semantic version' => [
            'value' => '/1.0/users',
            'routesPrefix' => '',
            'expectedVersion' => '1.0',
        ];

        yield 'versioned uri with semantic version multiple digits' => [
            'value' => '/2.5/users',
            'routesPrefix' => '',
            'expectedVersion' => '2.5',
        ];

        yield 'versioned uri with prefix' => [
            'value' => '/api/v1/users',
            'routesPrefix' => 'api',
            'expectedVersion' => 'v1',
        ];

        yield 'versioned uri with prefix and semantic version' => [
            'value' => '/api/1.0/users',
            'routesPrefix' => 'api',
            'expectedVersion' => '1.0',
        ];

        yield 'non-versioned uri returns default version' => [
            'value' => '/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'non-versioned uri with prefix returns default version' => [
            'value' => '/api/users',
            'routesPrefix' => 'api',
            'expectedVersion' => 'v1',
        ];

        yield 'uri with invalid version format returns default version' => [
            'value' => '/version1/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'uri with v but no number returns default version' => [
            'value' => '/v/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'uri with v and letters returns default version' => [
            'value' => '/vabc/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'empty uri returns default version' => [
            'value' => '',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'only slashes returns default version' => [
            'value' => '///',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'only prefix returns default version' => [
            'value' => '/api',
            'routesPrefix' => 'api',
            'expectedVersion' => 'v1',
        ];

        yield 'only version returns that version' => [
            'value' => '/v1',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'prefix and only version' => [
            'value' => '/api/v1',
            'routesPrefix' => 'api',
            'expectedVersion' => 'v1',
        ];

        yield 'version without leading slash' => [
            'value' => 'v1/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'multiple consecutive slashes with version' => [
            'value' => '//v1//users',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'version with trailing slash' => [
            'value' => '/v1/',
            'routesPrefix' => '',
            'expectedVersion' => 'v1',
        ];

        yield 'case sensitive prefix mismatch' => [
            'value' => '/API/v1/users', // <- API is treated as resource, not prefix
            'routesPrefix' => 'api',
            'expectedVersion' => 'v1', // <- Default value.
        ];

        yield 'version-like string in middle is not detected' => [
            'value' => '/users/v2/posts', // <- only first part after prefix is checked
            'routesPrefix' => '',
            'expectedVersion' => 'v1', // <- Default value.
        ];

        yield 'three digit semantic version returns empty' => [
            'value' => '/1.0.0/users', // <- regex only matches X.Y format
            'routesPrefix' => '',
            'expectedVersion' => 'v1', // <- Default value.
        ];

        yield 'v with multiple digits' => [
            'value' => '/v123/users',
            'routesPrefix' => '',
            'expectedVersion' => 'v123',
        ];

        yield 'semantic version with large numbers' => [
            'value' => '/99.99/users',
            'routesPrefix' => '',
            'expectedVersion' => '99.99',
        ];
    }

    #[DataProvider('resourceExtractionProvider')]
    public function test_it_extracts_resource_correctly(
        string $value,
        string $routesPrefix,
        string $expectedResource
    ): void {
        $uri = new VersionedUri(value: $value, routesPrefix: $routesPrefix);

        $this->assertEquals($expectedResource, $uri->getResource());
    }

    public static function resourceExtractionProvider(): Generator
    {
        yield 'versioned uri extracts resource after version' => [
            'value' => '/v1/users',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'versioned uri with semantic version extracts resource' => [
            'value' => '/1.0/users',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'versioned uri with prefix extracts resource' => [
            'value' => '/api/v1/users',
            'routesPrefix' => 'api',
            'expectedResource' => 'users',
        ];

        yield 'versioned uri with composed prefix extracts resource' => [
            'value' => '/cms/api/v1/users',
            'routesPrefix' => 'cms/api',
            'expectedResource' => 'users',
        ];

        yield 'versioned uri with multiple segments extracts first resource' => [
            'value' => '/v1/users/123/profile',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'non-versioned uri extracts resource' => [
            'value' => '/users',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'non-versioned uri with prefix extracts resource' => [
            'value' => '/api/users',
            'routesPrefix' => 'api',
            'expectedResource' => 'users',
        ];

        yield 'non-versioned uri with multiple segments' => [
            'value' => '/users/123',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'empty uri returns empty resource' => [
            'value' => '',
            'routesPrefix' => '',
            'expectedResource' => '',
        ];

        yield 'only slashes returns empty resource' => [
            'value' => '///',
            'routesPrefix' => '',
            'expectedResource' => '',
        ];

        yield 'only prefix returns empty resource' => [
            'value' => '/api',
            'routesPrefix' => 'api',
            'expectedResource' => '',
        ];

        yield 'only version returns empty resource' => [
            'value' => '/v1',
            'routesPrefix' => '',
            'expectedResource' => '', // <- no resource after version
        ];

        yield 'prefix and only version returns empty resource' => [
            'value' => '/api/v1',
            'routesPrefix' => 'api',
            'expectedResource' => '',
        ];

        yield 'version without leading slash' => [
            'value' => 'v1/users',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'multiple consecutive slashes with version' => [
            'value' => '//v1//users',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'resource without leading slash' => [
            'value' => 'users/123',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'versioned uri with trailing slash' => [
            'value' => '/v1/users/',
            'routesPrefix' => '',
            'expectedResource' => 'users',
        ];

        yield 'single character resource with version' => [
            'value' => '/v1/a',
            'routesPrefix' => '',
            'expectedResource' => 'a',
        ];

        yield 'numeric resource with version' => [
            'value' => '/v1/123',
            'routesPrefix' => '',
            'expectedResource' => '123',
        ];

        yield 'resource with special characters and version' => [
            'value' => '/v1/user-profile',
            'routesPrefix' => '',
            'expectedResource' => 'user-profile',
        ];

        yield 'case sensitive prefix mismatch' => [
            'value' => '/API/v1/users',
            'routesPrefix' => 'api',
            'expectedResource' => 'API', // <- API is treated as resource
        ];

        yield 'prefix as part of resource name' => [
            'value' => '/api/v1/api-users',
            'routesPrefix' => 'api',
            'expectedResource' => 'api-users',
        ];

        yield 'deeply nested versioned uri' => [
            'value' => '/api/v1/users/123/posts/456',
            'routesPrefix' => 'api',
            'expectedResource' => 'users',
        ];

        yield 'whitespace in resource with version' => [
            'value' => '/v1/ users /123',
            'routesPrefix' => '',
            'expectedResource' => ' users ',
        ];

        yield 'url encoded characters in resource' => [
            'value' => '/v1/user%20name',
            'routesPrefix' => '',
            'expectedResource' => 'user%20name',
        ];

        yield 'invalid version format treats first part as resource' => [
            'value' => '/version1/users',
            'routesPrefix' => '',
            'expectedResource' => 'version1', // <- not a valid version format
        ];

        yield 'three digit semantic version treats it as resource' => [
            'value' => '/1.0.0/users',
            'routesPrefix' => '',
            'expectedResource' => '1.0.0', // <- doesn't match version regex
        ];
    }

    #[TestWith(['/v1/users', ''])]
    #[TestWith(['/api/v2/posts', 'api'])]
    #[TestWith(['/1.0/resources', ''])]
    public function test_it_can_call_get_version_multiple_times(string $value, string $routesPrefix): void
    {
        $uri = new VersionedUri(value: $value, routesPrefix: $routesPrefix);

        // This asserts against mutating the original value.
        $firstCall = $uri->getVersion();
        $secondCall = $uri->getVersion();

        $this->assertEquals($firstCall, $secondCall);
        $this->assertNotEmpty($firstCall);
    }

    #[TestWith(['/v1/users'])]
    #[TestWith(['/api/v2/posts'])]
    #[TestWith(['/users'])]
    public function test_it_can_call_get_resource_multiple_times(string $value): void
    {
        $uri = new VersionedUri(value: $value, routesPrefix: '');

        // This asserts against mutating the original value.
        $firstCall = $uri->getResource();
        $secondCall = $uri->getResource();

        $this->assertEquals($firstCall, $secondCall);
    }
}
