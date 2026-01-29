<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\RoutesProcessors\Strategies;

use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractApplicationRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\AutoDetectRoutesProcessor;

#[CoversClass(AutoDetectRoutesProcessor::class)]
class AutoDetectRoutesProcessorUnitTest extends TestCase
{
    private AutoDetectRoutesProcessor $processor;

    private MockInterface&ExtractApplicationRoutesAction $extractActionMock;

    private MockInterface&Router $routerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractActionMock = Mockery::mock(ExtractApplicationRoutesAction::class);
        $this->routerMock = Mockery::mock(Router::class);

        $this->processor = new AutoDetectRoutesProcessor(
            extractRoutesAction: $this->extractActionMock,
            router: $this->routerMock,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_returns_correct_strategy_name(): void
    {
        // Act

        $name = $this->processor->getName();

        // Assert

        $this->assertEquals(RoutesProcessingStrategyEnum::AutoDetect, $name);
    }

    public function test_it_processes_and_returns_extracted_routes(): void
    {
        // Arrange

        $routesArray = ['route1', 'route2'];
        $expectedCollection = Mockery::mock(ExtractedRoutesCollection::class);

        $routeCollectionMock = Mockery::mock(RouteCollection::class);

        // Anticipate

        $routeCollectionMock->shouldReceive('getRoutes')
            ->once()
            ->andReturn($routesArray);

        $this->routerMock->shouldReceive('getRoutes')
            ->once()
            ->andReturn($routeCollectionMock);

        $this->extractActionMock->shouldReceive('execute')
            ->once()
            ->with($routesArray)
            ->andReturn($expectedCollection);

        // Act

        $result = $this->processor->process();

        // Assert

        $this->assertSame($expectedCollection, $result);
    }
}
