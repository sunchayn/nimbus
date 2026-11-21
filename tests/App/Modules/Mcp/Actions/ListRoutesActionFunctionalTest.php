<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListRoutesAction;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ListRoutesAction::class)]
class ListRoutesActionFunctionalTest extends TestCase
{
    use FakesExtractedRoutes;

    public function test_it_filters_routes_by_method(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(ListRoutesAction::class);

        // Act

        $result = $action->execute(method: 'get');

        // Assert

        $this->assertSame(
            ['GET api/users'],
            array_column($result['routes'], 'id'),
        );
    }

    public function test_it_filters_routes_by_search(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(ListRoutesAction::class);

        // Act

        $result = $action->execute(search: 'users.store');

        // Assert

        $this->assertSame(
            ['POST api/users'],
            array_column($result['routes'], 'id'),
        );

        $this->assertSame(
            ['email', 'name'],
            $result['routes'][0]['required'],
        );
    }

    public function test_it_limits_the_routes(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(ListRoutesAction::class);

        // Act

        $result = $action->execute(limit: 1);

        // Assert

        $this->assertCount(
            1,
            $result['routes'],
        );

        $this->assertTrue($result['truncated']);

        $this->assertSame(
            4,
            $result['total'],
        );
    }

    public function test_it_names_the_application_of_each_route_when_several_are_configured(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications', [
            'main' => config('nimbus.applications.main'),
            'other' => config('nimbus.applications.main'),
        ]);

        $action = resolve(ListRoutesAction::class);

        // Act

        $result = $action->execute(method: 'post');

        // Assert

        $this->assertSame(
            ['main', 'other'],
            array_column($result['routes'], 'application'),
        );
    }

    public function test_it_leaves_out_the_application_when_only_one_is_configured(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(ListRoutesAction::class);

        // Act

        $result = $action->execute(method: 'post');

        // Assert

        $this->assertArrayNotHasKey(
            'application',
            $result['routes'][0],
        );
    }

    public function test_it_lists_only_the_routes_of_the_asked_application(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications', [
            'main' => config('nimbus.applications.main'),
            'other' => config('nimbus.applications.main'),
        ]);

        $action = resolve(ListRoutesAction::class);

        // Act

        $result = $action->execute(method: 'post', application: 'other');

        // Assert

        $this->assertSame(
            ['other'],
            array_column($result['routes'], 'application'),
        );
    }

    public function test_it_filters_routes_by_prefix(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(ListRoutesAction::class);

        // Act

        $result = $action->execute(prefix: '/api/posts');

        // Assert

        $this->assertSame(
            ['PUT api/posts/{post}', 'PATCH api/posts/{post}'],
            array_column($result['routes'], 'id'),
        );
    }
}
