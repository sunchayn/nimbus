<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request;

use Illuminate\Routing\Route;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\RequestSchemaExtractor;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\FormRequestStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\InlineRequestValidatorStrategy;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\SpatieDataObjectStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RequestSchemaExtractor::class)]
class RequestSchemaExtractorFunctionalTest extends TestCase
{
    private FormRequestStrategy|MockInterface $formRequestStrategyMock;

    private SpatieDataObjectStrategy|MockInterface $spatieDataObjectStrategyMock;

    private InlineRequestValidatorStrategy|MockInterface $inlineRequestValidatorStrategyMock;

    private RequestSchemaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formRequestStrategyMock = $this->mock(FormRequestStrategy::class);
        $this->spatieDataObjectStrategyMock = $this->mock(SpatieDataObjectStrategy::class);
        $this->inlineRequestValidatorStrategyMock = $this->mock(InlineRequestValidatorStrategy::class);

        $this->extractor = $this->app->make(RequestSchemaExtractor::class);
    }

    public function test_it_delegates_to_strategies(): void
    {
        // Arrange

        $route = $this->mock(Route::class);

        $schema = new Schema([new StringSchemaProperty('name')]);

        // Anticipate

        $this->formRequestStrategyMock
            ->shouldReceive('attempt')
            ->with($route)
            ->once()
            ->andReturn(null);

        $this->spatieDataObjectStrategyMock
            ->shouldReceive('attempt')
            ->with($route)
            ->once()
            ->andReturn($schema);

        // Act

        $actual = $this->extractor->extract($route);

        // Assert

        $this->assertSame($schema, $actual);
    }

    public function test_it_returns_empty_schema_when_no_strategy_matches(): void
    {
        // Arrange

        $route = $this->mock(Route::class);

        // Anticipate

        $this->formRequestStrategyMock->shouldReceive('attempt')
            ->with($route)
            ->once()
            ->andReturn(null);

        $this->spatieDataObjectStrategyMock
            ->shouldReceive('attempt')
            ->with($route)
            ->once()
            ->andReturn(null);

        $this->inlineRequestValidatorStrategyMock
            ->shouldReceive('attempt')
            ->with($route)
            ->once()
            ->andReturn(null);

        // Act

        $actual = $this->extractor->extract($route);

        // Assert

        $this->assertTrue($actual->isEmpty());
    }

    public function test_it_handles_null_route(): void
    {
        // Act & Assert

        $actual = $this->extractor->extract(null);

        $this->assertTrue($actual->isEmpty());
    }
}
