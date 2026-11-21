<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use Illuminate\Contracts\Foundation\Application;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ExtractRoutesByApplicationAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ApplicationRoute;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExtractRoutesByApplicationAction::class)]
class ExtractRoutesByApplicationActionFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ExtractRoutesAction::class, function (Application $app): ExtractRoutesAction {
            $application = $app->make(ActiveApplicationResolver::class)->getActiveApplicationKey();

            $action = Mockery::mock(ExtractRoutesAction::class);

            $action
                ->shouldReceive('execute')
                ->andReturn(new ExtractedRoutesCollection([
                    new ExtractedRoute(new Endpoint('v1', 'users', "/{$application}/users"), ['GET'], Schema::empty()),
                ]));

            return $action;
        });
    }

    public function test_it_extracts_the_routes_of_each_application_while_it_is_the_active_one(): void
    {
        // Arrange

        $action = resolve(ExtractRoutesByApplicationAction::class);

        // Act

        $routesByApplication = $action->execute();

        // Assert

        $this->assertSame(
            ['main' => '/main/users', 'other' => '/other/users'],
            $routesByApplication
                ->perMethod()
                ->mapWithKeys(fn (ApplicationRoute $applicationRoute): array => [$applicationRoute->application => $applicationRoute->route->uri->value])
                ->all(),
        );

        $this->assertTrue($routesByApplication->isMultiApplication);
    }

    public function test_it_extracts_only_the_asked_application(): void
    {
        // Arrange

        $action = resolve(ExtractRoutesByApplicationAction::class);

        // Act

        $routesByApplication = $action->execute(application: 'other');

        // Assert

        $this->assertSame(
            ['other'],
            $routesByApplication->perMethod()->map(fn (ApplicationRoute $applicationRoute): string => $applicationRoute->application)->all(),
        );

        $this->assertTrue($routesByApplication->isMultiApplication);
    }
}
