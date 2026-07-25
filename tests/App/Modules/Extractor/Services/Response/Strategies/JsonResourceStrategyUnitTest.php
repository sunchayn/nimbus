<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Response\Strategies;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Ast\Actions\ResolveClassReturnTypeAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromResourceAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\JsonResourceStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(JsonResourceStrategy::class)]
class JsonResourceStrategyUnitTest extends TestCase
{
    private InferSchemaFromResourceAstAction $inferSchemaFromResourceAstActionMock;

    private ResolveClassReturnTypeAction $resolveClassReturnTypeActionMock;

    private JsonResourceStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inferSchemaFromResourceAstActionMock = Mockery::mock(InferSchemaFromResourceAstAction::class);

        $this->resolveClassReturnTypeActionMock = Mockery::mock(ResolveClassReturnTypeAction::class);

        $this->strategy = new JsonResourceStrategy(
            inferSchemaFromResourceAstAction: $this->inferSchemaFromResourceAstActionMock,
            resolveClassReturnTypeAction: $this->resolveClassReturnTypeActionMock
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

    public function test_it_returns_null_when_no_resource_found(): void
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

    public function test_it_extracts_using_resolved_type(): void
    {
        // Anticipate

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn('Class');
        $routeMock->shouldReceive('getActionMethod')->andReturn('method');

        $this->resolveClassReturnTypeActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('Class', 'method')
            ->andReturn(SomeDummyResource::class);

        $this->inferSchemaFromResourceAstActionMock
            ->shouldReceive('execute')
            ->with(SomeDummyResource::class)
            ->once()
            ->andReturn(
                $inferResponseStub = new Schema([new StringSchemaProperty('name')])
            );

        // Act

        $actual = $this->strategy->attempt($routeMock);

        // Assert

        $this->assertSame($inferResponseStub, $actual);
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
            ->andReturn(SomeDummyResource::class);

        $this->inferSchemaFromResourceAstActionMock
            ->shouldReceive('execute')
            ->once()
            ->andReturn(Schema::empty());

        // Act & Assert

        $this->assertNull($this->strategy->attempt($routeMock));
    }
}

class SomeDummyResource extends JsonResource {}
