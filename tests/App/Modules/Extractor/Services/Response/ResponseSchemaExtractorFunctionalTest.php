<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Response;

use Illuminate\Routing\Route;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromArrayAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\DataTransferObjects\ExtractableResponseData;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\ResponseSchemaExtractor;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\JsonResourceStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\RawJsonResponseStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\SpatieDataObjectResponseStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResponseSchemaExtractor::class)]
class ResponseSchemaExtractorFunctionalTest extends TestCase
{
    private JsonResourceStrategy|MockInterface $jsonResourceStrategyMock;

    private SpatieDataObjectResponseStrategy|MockInterface $spatieDataObjectResponseStrategyMock;

    private RawJsonResponseStrategy|MockInterface $rawJsonResponseStrategyMock;

    private InferSchemaFromArrayAction|MockInterface $inferSchemaFromArrayActionMock;

    private ResponseSchemaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jsonResourceStrategyMock = $this->mock(JsonResourceStrategy::class);
        $this->spatieDataObjectResponseStrategyMock = $this->mock(SpatieDataObjectResponseStrategy::class);
        $this->rawJsonResponseStrategyMock = $this->mock(RawJsonResponseStrategy::class);
        $this->inferSchemaFromArrayActionMock = $this->mock(InferSchemaFromArrayAction::class);

        $this->extractor = $this->app->make(ResponseSchemaExtractor::class);
    }

    public function test_it_delegates_to_strategies(): void
    {
        // Arrange

        $schema = new Schema([new StringSchemaProperty('dummy')]);

        $routeMock = $this->mock(Route::class);

        $data = new ExtractableResponseData($routeMock, null);

        // Anticipate

        $routeMock
            ->shouldReceive('getControllerClass')
            ->andReturn(null);

        $this
            ->jsonResourceStrategyMock
            ->shouldReceive('attempt')
            ->with($data->route)
            ->once()
            ->andReturn(null);

        $this
            ->spatieDataObjectResponseStrategyMock
            ->shouldReceive('attempt')
            ->with($data->route)
            ->once()
            ->andReturn($schema);

        // Act

        $actual = $this->extractor->extract($data);

        // Assert

        $this->assertSame($schema, $actual);
    }

    public function test_it_falls_back_to_concrete_payload_transformer_when_route_is_null(): void
    {
        // Arrange

        $data = new ExtractableResponseData(null, ['name' => 'Alice']);

        $fallbackSchema = new Schema([new StringSchemaProperty('name')]);

        // Anticipate

        $this->inferSchemaFromArrayActionMock->shouldReceive('execute')->with(['name' => 'Alice'])->once()->andReturn($fallbackSchema);

        // Act

        $actual = $this->extractor->extract($data);

        // Assert

        $this->assertSame($fallbackSchema, $actual);
    }

    public function test_it_falls_back_to_concrete_payload_transformer_when_strategies_yield_null(): void
    {
        // Arrange

        $routeMock = $this->mock(Route::class);
        $data = new ExtractableResponseData($routeMock, ['name' => 'Alice']);

        $fallbackSchema = new Schema([new StringSchemaProperty('name')]);

        // Anticipate

        $this->jsonResourceStrategyMock
            ->shouldReceive('attempt')
            ->with($routeMock)
            ->once()
            ->andReturn(null);

        $this->spatieDataObjectResponseStrategyMock
            ->shouldReceive('attempt')
            ->with($routeMock)
            ->once()
            ->andReturn(null);

        $this->rawJsonResponseStrategyMock
            ->shouldReceive('attempt')
            ->with($routeMock)
            ->once()
            ->andReturn(null);

        $this->inferSchemaFromArrayActionMock
            ->shouldReceive('execute')
            ->with(['name' => 'Alice'])
            ->once()
            ->andReturn($fallbackSchema);

        // Act

        $actual = $this->extractor->extract($data);

        // Assert

        $this->assertSame($fallbackSchema, $actual);
    }
}
