<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services;

use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(IgnoredRoutesService::class)]
class IgnoredRoutesServiceFunctionalTest extends TestCase
{
    private const FILE_PATH = 'vendor/sunchayn/nimbus/storage/ignored_routes.json';

    protected function setUp(): void
    {
        parent::setUp();

        File::delete(base_path(self::FILE_PATH));
        File::deleteDirectory(dirname(base_path(self::FILE_PATH)));
    }

    public function test_it_has_no_ignored_routes_initially(): void
    {
        // Arrange

        $service = resolve(IgnoredRoutesService::class);

        // Act

        $hasIgnoredRoutes = $service->hasIgnoredRoutes();

        // Assert

        $this->assertFalse($hasIgnoredRoutes);
    }

    public function test_it_adds_route_to_ignored_list(): void
    {
        // Arrange

        $service = resolve(IgnoredRoutesService::class);

        $route = $this->createMockRoute(uri: '/api/users', methods: ['GET', 'POST']);

        // Act

        $service->add(
            uri: $route->uri(),
            methods: $route->methods(),
            reason: 'Test reason'
        );

        // Assert

        $this->assertTrue($service->hasIgnoredRoutes());

        $this->assertTrue($service->isIgnored($route));
    }

    public function test_it_persists_ignored_routes_to_file_on_destruct(): void
    {
        // Arrange

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $service = resolve(IgnoredRoutesService::class);
        $service->add(uri: '/api/users', methods: ['GET'], reason: 'Test reason');

        $filePath = base_path(self::FILE_PATH);

        // Act

        $service->__destruct();

        // Assert

        $this->assertTrue(File::exists($filePath));

        $content = json_decode(File::get($filePath), true);

        $this->assertEquals(
            [
                '/api/users' => [
                    'methods' => ['GET'],
                    'reason' => 'Test reason',
                    'ignored_at' => CarbonImmutable::now()->toISOString(),
                ],
            ],
            $content,
        );
    }

    public function test_it_overwrites_existing_ignored_route(): void
    {
        // Arrange

        $service = resolve(IgnoredRoutesService::class);

        $service->add(uri: '/api/users', methods: ['GET'], reason: 'First reason');

        $service->add(uri: '/api/users', methods: ['POST'], reason: 'Second reason');

        $filePath = base_path(self::FILE_PATH);

        // Act

        $service->__destruct();

        // Assert

        $this->assertTrue(File::exists($filePath));

        $content = json_decode(File::get($filePath), true);

        $this->assertEquals(['POST'], $content['/api/users']['methods']);

        $this->assertEquals('Second reason', $content['/api/users']['reason']);
    }

    public function test_it_does_not_write_to_file_if_not_dirty(): void
    {
        // Arrange

        $service = resolve(IgnoredRoutesService::class);

        $filePath = base_path(self::FILE_PATH);

        // Act

        $service->__destruct();

        // Assert

        $this->assertFalse(File::exists($filePath));
    }

    #[DataProvider('routeIgnoredProvider')]
    public function test_it_checks_if_route_is_ignored(
        array $ignoredMethods,
        array $routeMethods,
        bool $expectedIgnored
    ): void {
        // Arrange

        $service = resolve(IgnoredRoutesService::class);

        $route = $this->createMockRoute('/api/users', $routeMethods);

        $service->add(uri: $route->uri(), methods: $ignoredMethods, reason: 'Test');

        // Act

        $isIgnored = $service->isIgnored($route);

        // Assert

        $this->assertEquals($expectedIgnored, $isIgnored);
    }

    public static function routeIgnoredProvider(): Generator
    {
        yield 'single method matches' => [
            'ignoredMethods' => ['GET'],
            'routeMethods' => ['GET'],
            'expectedIgnored' => true,
        ];

        yield 'one of multiple methods matches' => [
            'ignoredMethods' => ['GET', 'POST'],
            'routeMethods' => ['GET', 'PUT'],
            'expectedIgnored' => true,
        ];

        yield 'no methods match' => [
            'ignoredMethods' => ['GET'],
            'routeMethods' => ['POST', 'PUT'],
            'expectedIgnored' => false,
        ];

        yield 'all methods match' => [
            'ignoredMethods' => ['GET', 'POST', 'PUT'],
            'routeMethods' => ['GET', 'POST', 'PUT'],
            'expectedIgnored' => true,
        ];

        yield 'partial overlap' => [
            'ignoredMethods' => ['POST'],
            'routeMethods' => ['GET', 'POST'],
            'expectedIgnored' => true,
        ];
    }

    #[DataProvider('routeRemovalProvider')]
    public function test_it_removes_ignored_routes(
        array $initialMethods,
        array $removeMethods,
        array $routeMethods,
        bool $expectedIgnored
    ): void {
        // Arrange

        $service = resolve(IgnoredRoutesService::class);

        $route = $this->createMockRoute('/api/users', $routeMethods);

        $service->add(uri: $route->uri(), methods: $initialMethods, reason: 'Test');

        // Act

        $service->remove(uri: $route->uri(), methods: $removeMethods);

        // Assert

        $this->assertEquals($expectedIgnored, $service->isIgnored($route));
    }

    public static function routeRemovalProvider(): Generator
    {
        yield 'remove some methods' => [
            'initialMethods' => ['GET', 'POST', 'PUT'],
            'removeMethods' => ['GET', 'POST'],
            'routeMethods' => ['PUT'],
            'expectedIgnored' => true,
        ];

        yield 'remove all methods' => [
            'initialMethods' => ['GET', 'POST'],
            'removeMethods' => ['GET', 'POST'],
            'routeMethods' => ['GET', 'POST'],
            'expectedIgnored' => false,
        ];

        yield 'remove non-existent route' => [
            'initialMethods' => ['GET'],
            'removeMethods' => ['PATCH'],
            'routeMethods' => ['GET'],
            'expectedIgnored' => true,
        ];
    }

    public function test_it_handles_corrupted_json_file_gracefully(): void
    {
        // Arrange

        $filePath = base_path(self::FILE_PATH);

        $directory = dirname($filePath);

        File::ensureDirectoryExists($directory);

        File::put($filePath, 'invalid json content');

        // Act

        $service = resolve(IgnoredRoutesService::class);

        // Assert

        $this->assertFalse($service->hasIgnoredRoutes());
    }

    public function test_it_skips_empty_array_content(): void
    {
        // Arrange

        $filePath = base_path(self::FILE_PATH);

        $directory = dirname($filePath);

        File::ensureDirectoryExists($directory);

        File::put($filePath, '[]');

        $service = resolve(IgnoredRoutesService::class);

        // Act

        $hasIgnoredRoutes = $service->hasIgnoredRoutes();

        // Assert

        $this->assertFalse($hasIgnoredRoutes);
    }

    /*
     * Mocks.
     */

    private function createMockRoute(string $uri, array $methods): Route
    {
        $route = $this->createMock(Route::class);

        $route->method('uri')->willReturn($uri);
        $route->method('methods')->willReturn($methods);

        return $route;
    }
}
