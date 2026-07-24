<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies;

use Generator;
use Illuminate\Routing\Route;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\SpatieDataObjectStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs\SpatieDataObjectStub;
use Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs\StrategyControllerStub;

#[CoversClass(SpatieDataObjectStrategy::class)]
class SpatieDataObjectStrategyUnitTest extends TestCase
{
    private InferSchemaFromSpatieDataObjectAction $spatieDataToSchemaMock;

    private SpatieDataObjectStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spatieDataToSchemaMock = Mockery::mock(InferSchemaFromSpatieDataObjectAction::class);
        $this->strategy = new SpatieDataObjectStrategy($this->spatieDataToSchemaMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[DataProvider('attemptMatchingProvider')]
    public function test_it_returns_null_when_no_spatie_data_found(
        string $controllerMethod,
        bool $expectsNull
    ): void {
        // Arrange

        $route = $this->makeRoute($controllerMethod);

        // For matching paths that expect non-null, the strategy will call execute().
        $this->spatieDataToSchemaMock
            ->shouldReceive('execute')
            ->withAnyArgs()
            ->andReturn(new Schema([new StringSchemaProperty('data')]));

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertSame(
            $expectsNull,
            is_null($schema),
        );
    }

    public static function attemptMatchingProvider(): Generator
    {
        yield 'spatie data parameter triggers extraction' => [
            'controllerMethod' => 'withSpatieData',
            'expectsNull' => false,
        ];

        yield 'no parameters returns null' => [
            'controllerMethod' => 'withNoParams',
            'expectsNull' => true,
        ];

        yield 'parameter with no type hint returns null' => [
            'controllerMethod' => 'withUntypedParam',
            'expectsNull' => true,
        ];

        yield 'parameter with union type returns null' => [
            'controllerMethod' => 'withUnionParam',
            'expectsNull' => true,
        ];
    }

    public function test_it_extracts_schema_from_spatie_data_object(): void
    {
        // Arrange

        $route = $this->makeRoute('withSpatieData');

        // Anticipate

        $this->spatieDataToSchemaMock
            ->shouldReceive('execute')
            ->with(SpatieDataObjectStub::class)
            ->once()
            ->andReturn(
                $responseSchemaStub = new Schema([new StringSchemaProperty('data')]),
            );

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertSame($responseSchemaStub, $schema);
    }

    private function makeRoute(string $method): Route
    {
        $route = Mockery::mock(Route::class);

        $route->shouldReceive('getControllerClass')->andReturn(StrategyControllerStub::class);
        $route->shouldReceive('getActionMethod')->andReturn($method);

        return $route;
    }
}
