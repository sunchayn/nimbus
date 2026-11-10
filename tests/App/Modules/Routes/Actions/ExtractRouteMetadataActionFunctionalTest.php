<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRouteMetadataAction;
use Sunchayn\Nimbus\Tests\TestCase;
use Workbench\App\Http\Controllers\Demo\RouteMetadataController;
use Workbench\App\Models\Product;
use Workbench\App\Models\User;

#[CoversClass(ExtractRouteMetadataAction::class)]
class ExtractRouteMetadataActionFunctionalTest extends TestCase
{
    public function test_it_reads_the_metadata_of_a_protected_route(): void
    {
        // Arrange

        $route = RouteFacade::get('/products/{product:slug}', [RouteMetadataController::class, 'show'])
            ->middleware(['auth:sanctum', 'throttle:30,1', 'can:view,product'])
            ->where('product', '[a-z0-9-]+');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertSame('required', $metadata['auth']);
        $this->assertSame(['can:view,product'], $metadata['authHints']);
        $this->assertSame('30,1', $metadata['throttle']);
        $this->assertSame(['product' => '[a-z0-9-]+'], $metadata['where']);
        $this->assertSame([['parameter' => 'product', 'model' => Product::class, 'key' => 'slug']], $metadata['bindings']);
        $this->assertCount(3, $metadata['middleware']);
    }

    public function test_it_reports_a_guard_the_app_does_not_define_without_failing(): void
    {
        // Arrange

        $route = RouteFacade::get('/account', fn () => 'ok')->middleware('auth:sanctum,web');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertSame('required', $metadata['auth']);
        $this->assertSame(['sanctum'], $metadata['unknownGuards']);
    }

    public function test_it_does_not_report_a_defined_guard(): void
    {
        // Arrange

        $route = RouteFacade::get('/account', fn () => 'ok')->middleware('auth:web');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertArrayNotHasKey('unknownGuards', $metadata);
    }

    public function test_it_resolves_middleware_aliases_through_the_router(): void
    {
        // Arrange

        RouteFacade::aliasMiddleware('guard', Authenticate::class);

        $route = RouteFacade::get('/account', fn () => 'ok')->middleware('guard:web');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertSame('required', $metadata['auth']);
        $this->assertSame([Authenticate::class.':web'], $metadata['middleware']);
    }

    public function test_it_gives_no_auth_for_unknown_middleware(): void
    {
        // Arrange

        $route = RouteFacade::get('/open', fn () => 'ok')->middleware('some.custom.middleware');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertSame('none', $metadata['auth']);
        $this->assertSame(['some.custom.middleware'], $metadata['middleware']);
        $this->assertArrayNotHasKey('throttle', $metadata);
        $this->assertArrayNotHasKey('bindings', $metadata);
    }

    public function test_it_keeps_a_named_rate_limiter(): void
    {
        // Arrange

        $route = RouteFacade::get('/limited', fn () => 'ok')->middleware('throttle:api');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertSame('api', $metadata['throttle']);
    }

    public function test_it_reads_a_model_registered_with_route_model(): void
    {
        // Arrange

        RouteFacade::model('member', User::class);

        $route = RouteFacade::get('/members/{member}', fn () => 'ok');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertSame([['parameter' => 'member', 'model' => User::class, 'key' => null]], $metadata['bindings']);
    }

    public function test_it_removes_excluded_middleware(): void
    {
        // Arrange

        $route = RouteFacade::get('/public', fn () => 'ok')->middleware('auth')->withoutMiddleware('auth');

        // Act

        $metadata = resolve(ExtractRouteMetadataAction::class)->execute($route);

        // Assert

        $this->assertSame('none', $metadata['auth']);
    }
}
