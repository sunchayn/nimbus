<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Response\Strategies;

use Illuminate\Routing\Route;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Ast\Actions\ResolveClassReturnTypeAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\SpatieDataObjectResponseStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(SpatieDataObjectResponseStrategy::class)]
class SpatieDataObjectResponseStrategyUnitTest extends TestCase
{
    private InferSchemaFromSpatieDataObjectAction $inferSchemaFromSpatieDataObjectActionMock;

    private ResolveClassReturnTypeAction $resolveClassReturnTypeActionMock;

    private SpatieDataObjectResponseStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inferSchemaFromSpatieDataObjectActionMock = Mockery::mock(InferSchemaFromSpatieDataObjectAction::class);

        $this->resolveClassReturnTypeActionMock = Mockery::mock(ResolveClassReturnTypeAction::class);

        $this->strategy = new SpatieDataObjectResponseStrategy(
            $this->inferSchemaFromSpatieDataObjectActionMock,
            $this->resolveClassReturnTypeActionMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_returns_null_when_route_has_no_controller_class(): void
    {
        // Anticipate

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn(null);
        $routeMock->shouldReceive('getActionMethod')->andReturn('method');

        // Act & Assert

        $this->assertNull($this->strategy->attempt($routeMock));
    }

    public function test_it_returns_null_when_type_not_resolved(): void
    {
        // Anticipate

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn('Class');
        $routeMock->shouldReceive('getActionMethod')->andReturn('method');

        $this->resolveClassReturnTypeActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('Class', 'method')
            ->andReturn(null);

        // Act & Assert

        $this->assertNull($this->strategy->attempt($routeMock));
    }

    public function test_it_extracts_schema_successfully(): void
    {
        // Anticipate

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn('Class');
        $routeMock->shouldReceive('getActionMethod')->andReturn('method');

        $this->resolveClassReturnTypeActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('Class', 'method')
            ->andReturn(SomeDummyData::class);

        $this->inferSchemaFromSpatieDataObjectActionMock
            ->shouldReceive('execute')
            ->with(SomeDummyData::class)
            ->once()
            ->andReturn(
                $schema = new Schema([new StringSchemaProperty('name')]),
            );

        // Act

        $actual = $this->strategy->attempt($routeMock);

        // Assert

        $this->assertSame($schema, $actual);
    }

    public function test_it_returns_null_when_schema_is_empty(): void
    {
        // Anticipate

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn('Class');
        $routeMock->shouldReceive('getActionMethod')->andReturn('method');

        $this->resolveClassReturnTypeActionMock
            ->shouldReceive('execute')
            ->once()
            ->andReturn(SomeDummyData::class);

        $this->inferSchemaFromSpatieDataObjectActionMock
            ->shouldReceive('execute')
            ->once()
            ->andReturn(Schema::empty());

        // Act & Assert

        $this->assertNull($this->strategy->attempt($routeMock));
    }
}

class SomeDummyData extends \Spatie\LaravelData\Data {}
