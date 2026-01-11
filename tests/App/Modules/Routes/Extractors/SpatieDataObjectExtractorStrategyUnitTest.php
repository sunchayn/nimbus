<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors;

use Generator;
use Illuminate\Container\Container;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Resolvers\DataValidationRulesResolver;
use Spatie\LaravelData\Support\Validation\DataRules;
use Spatie\LaravelData\Support\Validation\ValidationPath;
use stdClass;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies\SpatieDataObjectExtractorStrategy;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs\SpatieDataObjectStub;

#[CoversClass(SpatieDataObjectExtractorStrategy::class)]
#[CoversClass(RulesExtractionError::class)]
class SpatieDataObjectExtractorStrategyUnitTest extends TestCase
{
    private SchemaBuilder $schemaBuilderMock;

    private Container&Mockery\MockInterface $containerMock;

    private SpatieDataObjectExtractorStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaBuilderMock = Mockery::mock(SchemaBuilder::class);
        $this->containerMock = Mockery::mock(Container::class);

        $this->strategy = new SpatieDataObjectExtractorStrategy($this->schemaBuilderMock, $this->containerMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[DataProvider('matchesProvider')]
    public function test_it_matches_correctly(
        array $parameterReflectionData,
        bool $expected
    ): void {
        // Arrange

        $parameters = array_map(
            fn (array $reflectionData) => $this->makeReflectionParameter($reflectionData),
            $parameterReflectionData
        );

        $route = $this->makeExtractableRoute($parameters);

        // Act

        $actual = $this->strategy->matches($route);

        // Assert

        $this->assertEquals($expected, $actual);
    }

    public static function matchesProvider(): Generator
    {
        yield 'spatie data object parameter' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => SpatieDataObjectStub::class], // <- ReflectionParameter methods return values.
            ],
            'expected' => true,
        ];

        yield 'base abstract data class' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => Data::class],
            ],
            'expected' => false,
        ];

        yield 'parameter without type' => [
            'parameterReflectionData' => [
                ['hasType' => false, 'getType' => null],
            ],
            'expected' => false,
        ];

        yield 'parameter with non-named type' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => null],
            ],
            'expected' => false,
        ];

        yield 'parameter not spatie data subclass' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => stdClass::class],
            ],
            'expected' => false,
        ];

        yield 'no parameters' => [
            'parameterReflectionData' => [],
            'expected' => false,
        ];

        yield 'multiple parameters with spatie data object' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => stdClass::class],
                ['hasType' => true, 'getType' => SpatieDataObjectStub::class],
            ],
            'expected' => true,
        ];

        yield 'multiple parameters without spatie data object' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => stdClass::class],
                ['hasType' => true, 'getType' => 'string'],
            ],
            'expected' => false,
        ];
    }

    public function test_it_extracts_schema_from_spatie_data_object(): void
    {
        // Arrange

        $parameter = $this->makeReflectionParameter([
            'getType' => $spatieDataObjectClass = SpatieDataObjectStub::class,
            'hasType' => true,
        ]);

        $route = $this->makeExtractableRoute([$parameter]);

        $spatieDataValidationRulesResolverMock = Mockery::mock(DataValidationRulesResolver::class);

        $dummyRules = ['dummy' => '::dummy_value::'];

        $expectedRules = Ruleset::fromLaravelRules($dummyRules);

        // Anticipate

        $spatieDataValidationRulesResolverMock
            ->shouldReceive('execute')
            ->withAnyArgs()
            ->andReturn($dummyRules)
            ->once();

        $this
            ->containerMock
            ->shouldReceive('make')
            ->with(DataValidationRulesResolver::class)
            ->andReturn($spatieDataValidationRulesResolverMock);

        $responseSchemaStub = new Schema(properties: [
            new StringSchemaProperty(
                name: '::property::',
            ),
        ]);

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->withAnyArgs()
            ->andReturn($responseSchemaStub)
            ->once();

        // Act

        $schema = $this->strategy->extract($route);

        // Assert

        $this->assertSame($responseSchemaStub, $schema);

        $this
            ->schemaBuilderMock
            ->shouldHaveReceived('buildSchemaFromRuleset')
            ->withArgs(function (Ruleset $ruleset, ?RulesExtractionError $rulesExtractionError) use ($expectedRules) {
                $this->assertEquals(
                    $expectedRules->all(),
                    $ruleset->all(),
                );

                $this->assertNull($rulesExtractionError);

                return true;
            })
            ->once();

        $spatieDataValidationRulesResolverMock
            ->shouldHaveReceived('execute')
            ->withArgs(
                function (
                    string $classArg,
                    array $fullPayloadArg,
                    ValidationPath $pathArg,
                    DataRules $dataRulesArg
                ) use ($spatieDataObjectClass) {
                    $this->assertEquals(
                        $spatieDataObjectClass,
                        $classArg,
                    );

                    $this->assertEmpty($fullPayloadArg);

                    $this->assertEquals(
                        '',
                        $pathArg->get(),
                    );

                    $this->assertEmpty(
                        $dataRulesArg->rules,
                    );

                    return true;
                })
            ->once();
    }

    #[RunInSeparateProcess] // <- Having overload Mock here, let's not leak it.
    #[PreserveGlobalState(false)]
    public function test_it_handles_exception_when_calling_rules_method(): void
    {
        // Arrange

        $parameter = $this->makeReflectionParameter([
            'hasType' => true,
            'getType' => SpatieDataObjectStub::class,
        ]);

        $route = $this->makeExtractableRoute([$parameter]);

        $responseSchemaStub = Schema::empty();

        $expectedRuleset = Ruleset::make([]);

        $spatieDataValidationRulesResolverMock = Mockery::mock(DataValidationRulesResolver::class);

        // Anticipate

        $spatieDataValidationRulesResolverMock
            ->shouldReceive('execute')
            ->withAnyArgs()
            ->andThrow(new \RuntimeException('Broken spatie data'))
            ->once();

        $this
            ->containerMock
            ->shouldReceive('make')
            ->with(DataValidationRulesResolver::class)
            ->andReturn($spatieDataValidationRulesResolverMock);

        $this
            ->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->withAnyArgs()
            ->andReturn($responseSchemaStub);

        // Act

        $schema = $this->strategy->extract($route);

        // Assert

        $this->assertSame($responseSchemaStub, $schema);

        $this
            ->schemaBuilderMock
            ->shouldHaveReceived('buildSchemaFromRuleset')
            ->withArgs(function (Ruleset $ruleset, ?RulesExtractionError $rulesExtractionError) use ($expectedRuleset) {
                $this->assertEquals(
                    $expectedRuleset->all(),
                    $ruleset->all(),
                );

                $this->assertNotNull($rulesExtractionError);

                $stubClassName = (new ReflectionClass(self::class))->getFileName();

                $this->assertEquals(
                    <<<HTML
<b>Broken spatie data</b><br />
<small>{$stubClassName}::260</small>
<p class="text-xs">[trace]</p>
HTML,
                    // TODO [Test] Test is not the right place to test this method.
                    // TODO [Test] Figure a way to fully test this properly. Due to final methods we cannot assert the trace properly.
                    preg_replace('#(<p\b[^>]*>).*?(</p>)#si', '$1[trace]$2', $rulesExtractionError->toHtml()),
                );

                return true;
            })
            ->once();
    }

    #[DataProvider('emptySchemaEarlyReturnProvider')]
    public function test_it_returns_early_with_empty_schema(array $parameterReflectionData): void
    {
        // Arrange

        $parameters = array_map(
            fn ($config) => $this->makeReflectionParameter($config),
            $parameterReflectionData
        );

        $route = $this->makeExtractableRoute($parameters);

        // Anticipate

        $this->schemaBuilderMock->shouldNotReceive('buildSchemaFromRuleset');

        // Act

        $schema = $this->strategy->extract($route);

        // Assert

        $this->assertTrue($schema->isEmpty());
    }

    public static function emptySchemaEarlyReturnProvider(): Generator
    {
        yield 'no spatie data object parameter' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => stdClass::class],
            ],
        ];

        yield 'parameter type is not named type' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => null],
            ],
        ];

        yield 'parameter type is not ReflectionNamedType' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => ReflectionUnionType::class],
            ],
        ];

        yield 'no parameters' => [
            'parameterReflectionData' => [],
        ];
    }

    /*
     * Generators.
     */

    private function makeExtractableRoute(array $parameters): ExtractableRoute
    {
        return new ExtractableRoute(
            parameters: $parameters,
            codeParser: fn () => 'noop',
        );
    }

    /**
     * @param  array{hasType: bool, getType: string}  $config
     */
    private function makeReflectionParameter(array $config): ReflectionParameter
    {
        $reflectionParameter = $this->createMock(ReflectionParameter::class);

        $reflectionParameter->method('hasType')->willReturn($config['hasType']);

        if ($config['getType'] !== null) {
            $type = $this->createMock(ReflectionNamedType::class);
            $type->method('getName')->willReturn($config['getType']);

            $reflectionParameter->method('getType')->willReturn($type);

            return $reflectionParameter;
        }

        $reflectionParameter->method('getType')->willReturn(null);

        return $reflectionParameter;
    }
}
