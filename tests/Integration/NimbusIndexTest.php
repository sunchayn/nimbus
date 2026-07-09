<?php

namespace Sunchayn\Nimbus\Tests\Integration;

use Generator;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Web\Controllers\NimbusIndexController;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Export\Services\ShareableLinkProcessorService;
use Sunchayn\Nimbus\Modules\Routes\Actions\BuildCurrentUserAction;
use Sunchayn\Nimbus\Modules\Routes\Actions\BuildGlobalHeadersAction;
use Sunchayn\Nimbus\Modules\Routes\Actions\DisableThirdPartyUiAction;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractApplicationRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\RoutesProcessorContract;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusIndexController::class)]
class NimbusIndexTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router
            ->post('/api/test', fn () => response()->json(['message' => 'success']))
            ->name('api.test');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Vite to prevent asset loading issues
        Vite::shouldReceive('useBuildDirectory')->with('vendor/nimbus')->atMost()->once();
        Vite::shouldReceive('useHotFile')->with(base_path('/vendor/sunchayn/nimbus/resources/dist/hot'))->atMost()->once();
        Vite::shouldReceive('__invoke')->andReturn('<script src="/nimbus/app.js"></script>')->atMost()->once();
    }

    #[DataProvider('indexRouteProvider')]
    public function test_it_loads_view_correctly(string $uri, ?string $applicationCookie = null, string $expectedApplicationKey = 'main'): void
    {
        // Arrange

        if ($applicationCookie) {
            $this->withCookie(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME, $applicationCookie);
        }

        $disableThirdPartyUiActionSpy = $this->spy(DisableThirdPartyUiAction::class);
        $ignoreRoutesServiceSpy = $this->spy(IgnoredRoutesService::class);
        $buildGlobalHeadersActionMock = $this->mock(BuildGlobalHeadersAction::class);
        $buildCurrentUserActionMock = $this->mock(BuildCurrentUserAction::class);
        $extractRoutesActionMock = $this->mock(ExtractApplicationRoutesAction::class);
        $shareableLinkProcessorMock = $this->mock(ShareableLinkProcessorService::class);

        // Anticipate

        $buildGlobalHeadersActionMock->shouldReceive('execute')->andReturn(['::global-headers::']);

        $buildCurrentUserActionMock->shouldReceive('execute')->andReturn(['::current-user::']);

        $shareableLinkProcessorMock->shouldReceive('process')->andReturnSelf();
        $shareableLinkProcessorMock->shouldReceive('getTargetApplication')->andReturnNull();
        $shareableLinkProcessorMock->shouldReceive('toFrontendState')->andReturnNull();

        $extractedRoutesCollectionStub = new class($expectedApplicationKey) extends ExtractedRoutesCollection
        {
            public function __construct(private string $key) {}

            public function toFrontendArray(): array
            {
                return ["routes for $this->key"];
            }
        };

        $extractRoutesActionMock->shouldReceive('execute')->andReturn($extractedRoutesCollectionStub);

        // Act

        $response = $this->get(route('nimbus.index').$uri);

        // Assert

        $response->assertStatus(200);

        $response->assertViewIs('nimbus::app');

        $response->assertViewHas('routes', ["routes for $expectedApplicationKey"]);

        $response->assertViewHas('headers', ['::global-headers::']);

        $response->assertViewHas('currentUser', ['::current-user::']);

        $response->assertViewHas('sharedState', null);

        $response->assertViewHas('activeApplicationResolver', function ($resolver) use ($expectedApplicationKey) {
            return $resolver->getActiveApplicationKey() === $expectedApplicationKey;
        });

        $disableThirdPartyUiActionSpy->shouldHaveReceived('execute')->once();

        $ignoreRoutesServiceSpy->shouldNotHaveReceived('execute');
    }

    public static function indexRouteProvider(): Generator
    {
        yield 'index route with default application' => [
            'uri' => '/',
            'applicationCookie' => null,
            'expectedApplicationKey' => 'main',
        ];

        yield 'index route with application from cookie' => [
            'uri' => '/',
            'applicationCookie' => 'other',
            'expectedApplicationKey' => 'other',
        ];

        yield 'index route with catch all parameter' => [
            'uri' => '/some/deep/path',
            'applicationCookie' => null,
            'expectedApplicationKey' => 'main',
        ];
    }

    public function test_it_ignores_routes(): void
    {
        // Arrange

        $ignoreData = [
            'uri' => '/api/test',
            'methods' => ['POST'],
            'reason' => 'Test ignore',
        ];

        $url = route('nimbus.index', ['ignore' => urlencode(json_encode($ignoreData))]);

        // Act

        $response = $this->get($url);

        // Assert

        $response
            ->assertStatus(302)
            ->assertRedirect('/nimbus');
    }

    public function test_it_catches_extraction_errors(): void
    {
        // Arrange

        $disableThirdPartyUiActionSpy = $this->spy(DisableThirdPartyUiAction::class);
        $ignoreRoutesServiceSpy = $this->spy(IgnoredRoutesService::class);
        $buildGlobalHeadersActionSpy = $this->spy(BuildGlobalHeadersAction::class);
        $buildCurrentUserActionSpy = $this->spy(BuildCurrentUserAction::class);
        $shareableLinkProcessorMock = $this->mock(ShareableLinkProcessorService::class);

        $extractionRoutesActionMock = $this->mock(ExtractApplicationRoutesAction::class);

        $exception = new class(message: fake()->words(asText: true), routeUri: fake()->url(), routeMethods: fake()->words(2), controllerClass: fake()->word(), controllerMethod: fake()->word(), suggestedSolution: fake()->words(asText: true)) extends RouteExtractionException {};

        // Anticipate

        $shareableLinkProcessorMock->shouldReceive('process')->andReturnSelf();
        $shareableLinkProcessorMock->shouldReceive('getTargetApplication')->andReturnNull();
        $shareableLinkProcessorMock->shouldReceive('toFrontendState')->andReturnNull();

        $extractionRoutesActionMock->shouldReceive('execute')->andThrow($exception);

        // Act

        $response = $this->get(route('nimbus.index'));

        // Assert

        $response->assertStatus(200);

        $response->assertViewIs('nimbus::app');

        $response->assertViewHas(
            'routeExtractorException',
            [
                'exception' => [
                    'message' => $exception->getMessage(),
                    'previous' => $exception->getPrevious() ? [
                        'message' => $exception->getPrevious()->getMessage(),
                        'file' => $exception->getPrevious()->getFile(),
                        'line' => $exception->getPrevious()->getLine(),
                    ] : null,
                ],
                'routeContext' => $exception->getRouteContext(),
                'suggestedSolution' => $exception->getSuggestedSolution(),
                'ignoreData' => $exception->getIgnoreData(),
            ],
        );

        $disableThirdPartyUiActionSpy->shouldHaveReceived('execute')->once();
        $ignoreRoutesServiceSpy->shouldNotHaveReceived('execute');
        $buildGlobalHeadersActionSpy->shouldNotHaveReceived('execute');
        $buildCurrentUserActionSpy->shouldNotHaveReceived('execute');
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->get(route('nimbus.index'));

        // Assert

        $response->assertStatus(200);

        $response->assertViewIs('nimbus::app');

        $response->assertViewHas(['routes', 'headers', 'currentUser']);
    }

    public function test_it_injects_the_configured_app_name(): void
    {
        // Arrange

        config(['nimbus.app_name' => 'Acme API']);

        // Act

        $response = $this->get(route('nimbus.index'));

        // Assert

        $response->assertStatus(200);

        // The name is embedded in the window.Nimbus config (Js::from output).
        $response->assertSee('Acme API', false);
    }

    public function test_it_falls_back_to_the_application_name_when_app_name_is_null(): void
    {
        // Arrange

        config(['nimbus.app_name' => null, 'app.name' => 'Host Application']);

        // Act

        $response = $this->get(route('nimbus.index'));

        // Assert

        $response->assertStatus(200);
        $response->assertSee('Host Application', false);
    }

    public function test_it_redirects_to_new_application(): void
    {
        // Arrange

        $url = route('nimbus.index', ['application' => 'new-app']);

        // Act

        $response = $this->get($url);

        // Assert

        $response->assertRedirect(route('nimbus.index').'?');

        $response->assertCookie(
            \Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME,
            'new-app',
        );
    }

    public function test_it_processes_valid_shareable_link(): void
    {
        // Arrange

        $payload = [
            'method' => 'GET',
            'endpoint' => '/api/test',
            'headers' => [],
            'queryParameters' => [],
            'body' => [],
            'payloadType' => 'empty',
            'authorization' => ['type' => 'none'],
        ];

        $encoded = base64_encode(json_encode($payload)); // <- this is dummy payload, we are mocking the interaction.

        $shareableLinkProcessorMock = $this->mock(ShareableLinkProcessorService::class);

        // Anticipate

        $shareableLinkProcessorMock
            ->shouldReceive('process')
            ->with($encoded)
            ->andReturnSelf();

        $shareableLinkProcessorMock
            ->shouldReceive('getTargetApplication')->andReturnNull();

        $shareableLinkProcessorMock
            ->shouldReceive('toFrontendState')
            ->andReturn([
                'payload' => $payload,
                'routeExists' => true,
                'error' => null,
            ]);

        // Act

        $response = $this->get(route('nimbus.index', ['share' => $encoded]));

        // Assert

        $response->assertStatus(200);

        $response->assertViewHas('sharedState', [
            'payload' => $payload,
            'routeExists' => true,
            'error' => null,
        ]);
    }

    public function test_it_handles_shareable_link_with_error(): void
    {
        // Arrange

        $invalidPayload = 'invalid-base64-string';

        $shareableLinkProcessorMock = $this->mock(ShareableLinkProcessorService::class);

        // Anticipate

        $shareableLinkProcessorMock
            ->shouldReceive('process')
            ->with($invalidPayload)
            ->andReturnSelf();

        $shareableLinkProcessorMock
            ->shouldReceive('getTargetApplication')->andReturnNull();

        $shareableLinkProcessorMock
            ->shouldReceive('toFrontendState')
            ->andReturn([
                'payload' => null,
                'routeExists' => false,
                'error' => 'Failed to decode base64 payload',
            ]);

        // Act

        $response = $this->get(route('nimbus.index', ['share' => $invalidPayload]));

        // Assert

        $response->assertStatus(200);

        $response->assertViewHas('sharedState', function ($state) {
            return $state['error'] === 'Failed to decode base64 payload'
                && $state['routeExists'] === false
                && $state['payload'] === null;
        });
    }

    public function test_it_redirects_when_shareable_link_targets_different_application(): void
    {
        // Arrange

        $payload = [
            'method' => 'GET',
            'endpoint' => '/api/test',
            'headers' => [],
            'queryParameters' => [],
            'body' => [],
            'payloadType' => 'empty',
            'authorization' => ['type' => 'none'],
            'applicationKey' => 'other-app',
        ];

        $encoded = base64_encode(json_encode($payload)); // <- this is dummy payload, we are mocking the interaction.

        $shareableLinkProcessorMock = $this->mock(ShareableLinkProcessorService::class);

        $activeApplicationResolverMock = $this->mock(ActiveApplicationResolver::class);

        $routesProviderMock = $this->mock(RoutesProcessorContract::class);

        // Anticipate

        $activeApplicationResolverMock
            ->shouldReceive('getActiveApplicationKey')
            ->andReturn('main-app');

        $shareableLinkProcessorMock
            ->shouldReceive('process')
            ->with($encoded)
            ->andReturnSelf();

        $shareableLinkProcessorMock
            ->shouldReceive('getTargetApplication')
            ->andReturn('other-app');

        // Act

        $response = $this->get(route('nimbus.index', ['share' => $encoded]));

        // Assert

        $response->assertRedirect();

        $response->assertCookie(
            \Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME,
            'other-app',
        );
    }

    public function test_it_does_not_redirect_when_shareable_link_targets_same_application(): void
    {
        // Arrange

        $payload = [
            'method' => 'GET',
            'endpoint' => '/api/test',
            'headers' => [],
            'queryParameters' => [],
            'body' => [],
            'payloadType' => 'empty',
            'authorization' => ['type' => 'none'],
            'applicationKey' => 'main-app',
        ];

        $encoded = base64_encode(json_encode($payload));

        $disableThirdPartyUiActionSpy = $this->spy(DisableThirdPartyUiAction::class);
        $buildGlobalHeadersActionMock = $this->mock(BuildGlobalHeadersAction::class);
        $buildCurrentUserActionMock = $this->mock(BuildCurrentUserAction::class);
        $shareableLinkProcessorMock = $this->mock(ShareableLinkProcessorService::class);
        $activeApplicationResolverMock = $this->mock(ActiveApplicationResolver::class);
        $routesProviderMock = $this->mock(RoutesProcessorContract::class);

        // Anticipate

        $activeApplicationResolverMock
            ->shouldReceive('getActiveApplicationKey')
            ->andReturn('main-app');

        $activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(false);
        $activeApplicationResolverMock->shouldReceive('getApiBaseUrl')->andReturn('http://localhost');
        $activeApplicationResolverMock->shouldReceive('getAvailableApplications')->andReturn('{}');
        $activeApplicationResolverMock->shouldReceive('showOperationId')->andReturn(false);

        $buildGlobalHeadersActionMock->shouldReceive('execute')->andReturn(['::global-headers::']);
        $buildCurrentUserActionMock->shouldReceive('execute')->andReturn(['::current-user::']);

        $shareableLinkProcessorMock
            ->shouldReceive('process')
            ->with($encoded)
            ->andReturnSelf();

        $shareableLinkProcessorMock
            ->shouldReceive('getTargetApplication')
            ->andReturn('main-app');

        $shareableLinkProcessorMock
            ->shouldReceive('toFrontendState')
            ->andReturn([
                'payload' => $payload,
                'routeExists' => true,
                'error' => null,
            ]);

        $routesProviderMock->shouldReceive('getName')->andReturn(RoutesProcessingStrategyEnum::AutoDetect);
        $routesProviderMock->shouldReceive('process')->andReturn($this->mock(ExtractedRoutesCollection::class)->shouldReceive('toFrontendArray')->andReturn([])->getMock());

        // Act

        $response = $this->get(route('nimbus.index', ['share' => $encoded]));

        // Assert

        $response->assertStatus(200);

        $response->assertViewIs('nimbus::app');

        $response->assertViewHas('sharedState', [
            'payload' => $payload,
            'routeExists' => true,
            'error' => null,
        ]);

        $disableThirdPartyUiActionSpy->shouldHaveReceived('execute')->once();
    }

    #[DataProvider('basePathDataProvider')]
    public function test_it_calculates_base_path_correctly(string $appUrl, string $prefix, string $expectedBasePath): void
    {
        // Arrange

        config(['app.url' => $appUrl]);
        config(['nimbus.prefix' => $prefix]);

        // Act

        $response = $this->get(route('nimbus.index'));

        // Assert

        $response->assertStatus(200);

        // We check for the basePath in the window.Nimbus config.
        // Js::from() produces a JSON.parse() call where slashes are escaped with triple backslashes in the output string
        $escapedPath = str_replace('/', '\\\\\\/', $expectedBasePath);
        $response->assertSee($escapedPath, false);
    }

    public static function basePathDataProvider(): Generator
    {
        yield 'standard url and prefix' => [
            'appUrl' => 'http://localhost',
            'prefix' => 'nimbus',
            'expectedBasePath' => '/nimbus',
        ];

        yield 'app in subdirectory' => [
            'appUrl' => 'http://localhost/my-app',
            'prefix' => 'nimbus',
            'expectedBasePath' => '/my-app/nimbus',
        ];

        yield 'app in deeper subdirectory' => [
            'appUrl' => 'http://localhost/deep/path/app',
            'prefix' => 'nimbus',
            'expectedBasePath' => '/deep/path/app/nimbus',
        ];

        yield 'prefix with leading/trailing slashes' => [
            'appUrl' => 'http://localhost/my-app',
            'prefix' => '/nimbus/',
            'expectedBasePath' => '/my-app/nimbus',
        ];

        yield 'app url with trailing slash' => [
            'appUrl' => 'http://localhost/my-app/',
            'prefix' => 'nimbus',
            'expectedBasePath' => '/my-app/nimbus',
        ];
    }
}
