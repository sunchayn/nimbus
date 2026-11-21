<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ApplicationRoute;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\RoutesByApplication;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

#[CoversClass(RoutesByApplication::class)]
#[CoversClass(ApplicationRoute::class)]
class RoutesByApplicationUnitTest extends TestCase
{
    public function test_it_finds_a_route_that_exists_in_one_application(): void
    {
        // Arrange

        $routesByApplication = $this->routesByApplication();

        // Act

        $applicationRoute = $routesByApplication->findRouteOrFail('GET api/orders');

        // Assert

        $this->assertSame(
            'admin',
            $applicationRoute->application,
        );
    }

    public function test_it_fails_as_ambiguous_and_names_the_applications(): void
    {
        // Arrange

        $routesByApplication = $this->routesByApplication();

        // Assert

        try {
            $routesByApplication->findRouteOrFail('GET api/users');

            $this->fail('The reference should be ambiguous.');
        } catch (McpToolException $mcpToolException) {
            $this->assertSame(
                McpToolException::ROUTE_AMBIGUOUS,
                $mcpToolException->errorCode,
            );

            $this->assertStringContainsString(
                '`admin`, `public`',
                $mcpToolException->getMessage(),
            );

            $this->assertStringContainsString(
                '`application`',
                $mcpToolException->hint,
            );
        }
    }

    public function test_it_suggests_the_nearest_ids_for_an_unknown_route(): void
    {
        // Arrange

        $routesByApplication = $this->routesByApplication();

        // Assert

        try {
            $routesByApplication->findRouteOrFail('GET api/order');

            $this->fail('The reference should not match.');
        } catch (RouteNotFoundException $routeNotFoundException) {
            $this->assertContains(
                'GET api/orders',
                $routeNotFoundException->suggestions,
            );
        }
    }

    public function test_it_lists_one_entry_per_application_route(): void
    {
        // Arrange

        $routesByApplication = $this->routesByApplication();

        // Act

        $entries = $routesByApplication
            ->perMethod()
            ->map(fn (ApplicationRoute $applicationRoute): string => $applicationRoute->application.' '.$applicationRoute->route->id())
            ->all();

        // Assert

        $this->assertSame(
            ['admin GET api/users', 'admin GET api/orders', 'public GET api/users'],
            $entries,
        );
    }

    /*
     * Generators.
     */

    private function routesByApplication(): RoutesByApplication
    {
        return new RoutesByApplication(
            byApplication: [
                'admin' => new ExtractedRoutesCollection([
                    $this->route('/api/users'),
                    $this->route('/api/orders'),
                ]),
                'public' => new ExtractedRoutesCollection([
                    $this->route('/api/users'),
                ]),
            ],
            isMultiApplication: true,
        );
    }

    private function route(string $uri): ExtractedRoute
    {
        return new ExtractedRoute(
            new Endpoint('v1', 'resource', $uri),
            ['GET'],
            Schema::empty(),
        );
    }
}
