<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Spatie\LaravelData\Resolvers\DataValidationRulesResolver;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAction;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(InferSchemaFromSpatieDataObjectAction::class)]
class InferSchemaFromSpatieDataObjectActionFunctionalTest extends TestCase
{
    public function test_it_transforms_spatie_data_rules_to_schema(): void
    {
        // Arrange

        $schemaBuilderMock = $this->mock(SchemaBuilder::class);

        $resolverMock = $this->mock(DataValidationRulesResolver::class);

        $schema = new Schema([]);

        $action = new InferSchemaFromSpatieDataObjectAction($schemaBuilderMock, $this->app);

        // Anticipate

        $resolverMock
            ->shouldReceive('execute')
            ->once()
            ->andReturn(['name' => ['required', 'string']]);

        $schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->with(\Mockery::on(fn (Ruleset $rules) => $rules->all() === ['name' => ['required', 'string']]))
            ->andReturn($schema);

        // Act

        $result = $action->execute('SomeSpatieDataClass');

        // Assert

        $this->assertSame($schema, $result);
    }

    public function test_it_handles_exceptions_by_passing_rules_extraction_error(): void
    {
        // Arrange

        $schemaBuilderMock = $this->mock(SchemaBuilder::class);

        $schema = new Schema([]);

        // Anticipate

        $this->mock(
            DataValidationRulesResolver::class,
            function (MockInterface $mock) {
                $mock
                    ->shouldReceive('execute')
                    ->andThrow(new \Exception('Test exception'));
            }
        );

        $schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->with(\Mockery::on(fn (Ruleset $rules) => $rules->isEmpty()), \Mockery::any())
            ->andReturn($schema);

        $action = new InferSchemaFromSpatieDataObjectAction($schemaBuilderMock, $this->app);

        // Act

        $result = $action->execute('SomeSpatieDataClass');

        // Assert

        $this->assertSame($schema, $result);
    }
}
