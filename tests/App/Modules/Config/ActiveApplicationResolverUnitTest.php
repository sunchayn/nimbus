<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Config;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ActiveApplicationResolver::class)]
class ActiveApplicationResolverUnitTest extends TestCase
{
    private Repository|MockInterface $configMock;

    private Request|MockInterface $requestMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configMock = Mockery::mock(Repository::class);
        $this->requestMock = Mockery::mock(Request::class);
    }

    public function test_it_throws_exception_if_no_applications_defined(): void
    {
        // Arrange

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn([]);

        // Anticipate

        $this->expectException(MisconfiguredValueException::class);
        $this->expectExceptionMessage('There are no applications defined.');

        // Act

        new ActiveApplicationResolver($this->configMock, $this->requestMock);
    }

    public function test_it_throws_exception_if_default_application_is_invalid(): void
    {
        // Arrange

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['p1' => []]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('invalid');

        // Anticipate

        $this->expectException(MisconfiguredValueException::class);
        $this->expectExceptionMessage("The default application `invalid` doesn't have a matching configuration.");

        // Act

        new ActiveApplicationResolver($this->configMock, $this->requestMock);
    }

    public function test_it_resolves_application_from_cookie(): void
    {
        // Arrange

        $applications = [
            'p1' => ['name' => 'Project 1'],
            'p2' => ['name' => 'Project 2'],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn($applications);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn('p2');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.p2', [])->andReturn($applications['p2']);

        // Act

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Assert

        $this->assertEquals('p2', $resolver->getActiveApplicationKey());
    }

    public function test_it_falls_back_to_default_if_cookie_is_invalid(): void
    {
        // Arrange

        $applications = [
            'p1' => ['name' => 'Project 1'],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn($applications);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn('invalid');
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('p1');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.p1', [])->andReturn($applications['p1']);

        // Act

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Assert

        $this->assertEquals('p1', $resolver->getActiveApplicationKey());
    }

    public function test_it_provides_specialized_getters_with_defaults(): void
    {
        // Arrange

        $applications = ['main' => []];
        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn($applications);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn([]);
        $this->requestMock->shouldReceive('getSchemeAndHttpHost')->andReturn('http://localhost');

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertFalse($resolver->isVersioned());
        $this->assertEquals('http://localhost', $resolver->getApiBaseUrl());
        $this->assertEquals('api', $resolver->getRoutesPrefix());
        $this->assertEquals('web', $resolver->getAuthGuard());
        $this->assertNull($resolver->getSpecialAuthInjector());
        $this->assertEquals([], $resolver->getHeaders());
    }

    public function test_it_provides_specialized_getters_from_config(): void
    {
        // Arrange

        $config = [
            'routes' => [
                'versioned' => true,
                'api_base_url' => 'https://api.example.com',
                'prefix' => 'v1',
            ],
            'auth' => [
                'guard' => 'api',
                'special' => ['injector' => 'SomeInjector'],
            ],
            'headers' => ['X-Test' => 'value'],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertTrue($resolver->isVersioned());
        $this->assertEquals('https://api.example.com', $resolver->getApiBaseUrl());
        $this->assertEquals('v1', $resolver->getRoutesPrefix());
        $this->assertEquals('api', $resolver->getAuthGuard());
        $this->assertEquals('SomeInjector', $resolver->getSpecialAuthInjector());
        $this->assertEquals(['X-Test' => 'value'], $resolver->getHeaders());
    }

    public function test_it_returns_available_applications_as_json(): void
    {
        // Arrange

        $applications = [
            'p1' => ['name' => 'Project 1'],
            'p2' => [], // Should fallback to key if name missing
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn($applications);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('p1');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.p1', [])->andReturn($applications['p1']);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act

        $available = $resolver->getAvailableApplications();

        // Assert

        $this->assertJson($available);
        $this->assertEquals(['p1' => 'Project 1', 'p2' => 'p2'], json_decode($available, true));
    }

    public function test_it_reconciles_special_auth_injector_aliases(): void
    {
        // Arrange

        $config = [
            'auth' => [
                'special' => ['injector' => 'remember_me_cookie'],
            ],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertEquals(\Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Injectors\RememberMeCookieInjector::class, $resolver->getSpecialAuthInjector());
    }

    public function test_it_reconciles_tymon_jwt_injector_alias(): void
    {
        // Arrange

        $config = [
            'auth' => [
                'special' => ['injector' => 'tymon_jwt'],
            ],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertEquals(\Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Injectors\TymonJwtTokenInjector::class, $resolver->getSpecialAuthInjector());
    }

    public function test_it_reconciles_route_processing_strategy(): void
    {
        // Arrange

        $config = [
            'routes' => [
                'strategy' => 'auto_detect',
            ],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertEquals(\Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum::AutoDetect, $resolver->getRouteExtractionStrategy());
    }

    public function test_it_throws_exception_on_invalid_route_processing_strategy(): void
    {
        // Arrange

        $config = [
            'routes' => [
                'strategy' => 'invalid_strategy_name',
            ],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Anticipate

        $this->expectException(MisconfiguredValueException::class);
        $this->expectExceptionMessage('The configured route processing strategy `invalid_strategy_name` is invalid.');

        // Act

        $resolver->getRouteExtractionStrategy();
    }

    public function test_it_returns_show_operation_id_and_openapi_files(): void
    {
        // Arrange

        $config = [
            'routes' => [
                'openapi' => [
                    'show_operation_id' => true,
                    'files' => ['v1' => '/path/to/v1.yaml'],
                ],
            ],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertTrue($resolver->showOperationId());
        $this->assertEquals(['v1' => '/path/to/v1.yaml'], $resolver->getOpenApiFiles());
    }

    public function test_it_returns_null_when_special_auth_injector_is_empty_or_non_string(): void
    {
        // Arrange

        $config = [
            'auth' => [
                'special' => ['injector' => ''],
            ],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertNull($resolver->getSpecialAuthInjector());
    }

    public function test_it_returns_null_when_special_auth_injector_is_non_string(): void
    {
        // Arrange

        $config = [
            'auth' => [
                'special' => ['injector' => 123],
            ],
        ];

        $this->configMock->shouldReceive('get')->with('nimbus.applications', [])->andReturn(['main' => $config]);
        $this->requestMock->shouldReceive('cookie')->with(ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME)->andReturn(null);
        $this->configMock->shouldReceive('get')->with('nimbus.default_application')->andReturn('main');
        $this->configMock->shouldReceive('get')->with('nimbus.applications.main', [])->andReturn($config);

        $resolver = new ActiveApplicationResolver($this->configMock, $this->requestMock);

        // Act & Assert

        $this->assertNull($resolver->getSpecialAuthInjector());
    }
}
