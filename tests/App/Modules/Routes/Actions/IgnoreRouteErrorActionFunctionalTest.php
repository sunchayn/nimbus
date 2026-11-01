<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Sunchayn\Nimbus\Modules\Routes\Actions\IgnoreRouteErrorAction;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(IgnoreRouteErrorAction::class)]
class IgnoreRouteErrorActionFunctionalTest extends TestCase
{
    public function test_it_ignores_routes_when_applicable(): void
    {
        // Arrange

        $ignoredRoutesServiceSpy = $this->spy(IgnoredRoutesService::class);

        $ignoredRouteErrorAction = resolve(IgnoreRouteErrorAction::class);

        $uri = 'api/users';
        $methods = ['GET', 'POST'];

        $ignoreData = $uri.'|'.json_encode($methods);

        // Act

        $ignoredRouteErrorAction->execute($ignoreData);

        // Assert

        $ignoredRoutesServiceSpy
            ->shouldHaveReceived(
                'add',
                function (string $uriArg, array $methodsArg) use ($uri, $methods) {
                    $this->assertEquals(
                        $uri,
                        $uriArg,
                    );

                    $this->assertEquals(
                        $methods,
                        $methodsArg,
                    );

                    return true;
                },
            );
    }

    public function test_it_clears_the_routes_snapshots_when_a_route_is_ignored(): void
    {
        // Arrange

        $this->spy(IgnoredRoutesService::class);

        $manifestManagerSpy = $this->spy(ManifestManager::class);

        $ignoredRouteErrorAction = resolve(IgnoreRouteErrorAction::class);

        // Act

        $ignoredRouteErrorAction->execute('api/users|["GET"]');

        // Assert

        $manifestManagerSpy->shouldHaveReceived('clearAll')->once();
    }

    public function test_it_gracefully_workaround_invalid_methods_structure(): void
    {
        // Arrange

        $ignoredRoutesServiceSpy = $this->spy(IgnoredRoutesService::class);

        $ignoredRouteErrorAction = resolve(IgnoreRouteErrorAction::class);

        $uri = 'api/users';
        $methods = ['GET', 'POST'];

        $ignoreData = $uri.'|'.implode(',', $methods);

        // Act

        $ignoredRouteErrorAction->execute($ignoreData);

        // Assert

        $ignoredRoutesServiceSpy
            ->shouldHaveReceived(
                'add',
                function (string $uriArg, array $methodsArg) use ($uri) {
                    $this->assertEquals(
                        $uri,
                        $uriArg,
                    );

                    $this->assertEquals(
                        [], // <- Didn't use the methods and used empty array instead.
                        $methodsArg,
                    );

                    return true;
                },
            );
    }

    #[TestWith(['foobar'], 'Missing Parts')]
    #[TestWith(['foobar|["get","post"]|foobaz'], 'Extra Parts')]
    #[TestWith(['|["get","post"]'], 'Empty URI')]
    public function test_it_does_nothing_with_broken_input(string $ignoreData): void
    {
        // Arrange

        $ignoredRoutesServiceSpy = $this->spy(IgnoredRoutesService::class);

        $ignoredRouteErrorAction = resolve(IgnoreRouteErrorAction::class);

        // Act

        $ignoredRouteErrorAction->execute($ignoreData);

        // Assert

        $ignoredRoutesServiceSpy->shouldNotHaveReceived('add');
    }
}
