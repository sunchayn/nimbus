<?php

namespace Sunchayn\Nimbus\Tests\Integration;

use Generator;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Web\Controllers\NimbusIndexController;
use Sunchayn\Nimbus\Modules\Routes\Actions\BuildCurrentUserAction;
use Sunchayn\Nimbus\Modules\Routes\Actions\BuildGlobalHeadersAction;
use Sunchayn\Nimbus\Modules\Routes\Actions\DisableThirdPartyUiAction;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;
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
        Vite::shouldReceive('useBuildDirectory')->with('/vendor/nimbus')->atMost()->once();
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
        $extractRoutesActionMock = $this->mock(ExtractRoutesAction::class);

        // Anticipate

        $buildGlobalHeadersActionMock->shouldReceive('execute')->andReturn(['::global-headers::']);

        $buildCurrentUserActionMock->shouldReceive('execute')->andReturn(['::current-user::']);

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

        $extractionRoutesActionMock = $this->mock(ExtractRoutesAction::class);

        $exception = new class(message: fake()->words(asText: true), routeUri: fake()->url(), routeMethods: fake()->words(2), controllerClass: fake()->word(), controllerMethod: fake()->word(), suggestedSolution: fake()->words(asText: true)) extends RouteExtractionException {};

        // Anticipate

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
}
