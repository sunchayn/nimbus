<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors;

use Generator;
use Illuminate\Http\Request;
use Mockery;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Ast\RulesMethodVisitor;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies\FormRequestExtractorStrategy;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs\FormRequestStub;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs\FormRequestWithDifferentRulesStub;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs\FormRequestWithExceptionStub;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs\FormRequestWithoutRulesStub;

#[CoversClass(FormRequestExtractorStrategy::class)]
#[CoversClass(RulesExtractionError::class)]
class FormRequestExtractorStrategyUnitTest extends TestCase
{
    private SchemaBuilder $schemaBuilderMock;

    private FormRequestExtractorStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaBuilderMock = Mockery::mock(SchemaBuilder::class);

        $this->strategy = new FormRequestExtractorStrategy($this->schemaBuilderMock);
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
        yield 'form request parameter' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => FormRequestStub::class], // <- ReflectionParameter methods return values.
            ],
            'expected' => true,
        ];

        yield 'base request parameter' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => Request::class],
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

        yield 'parameter not request subclass' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => \stdClass::class],
            ],
            'expected' => false,
        ];

        yield 'no parameters' => [
            'parameterReflectionData' => [],
            'expected' => false,
        ];

        yield 'multiple parameters with form request' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => \stdClass::class],
                ['hasType' => true, 'getType' => FormRequestStub::class],
            ],
            'expected' => true,
        ];

        yield 'multiple parameters without form request' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => \stdClass::class],
                ['hasType' => true, 'getType' => 'string'],
            ],
            'expected' => false,
        ];

        yield 'form request after base request' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => Request::class],
                ['hasType' => true, 'getType' => FormRequestStub::class],
            ],
            'expected' => false, // <- stops at base Request
        ];
    }

    #[DataProvider('extractProvider')]
    public function test_it_extracts_schema_from_form_request(
        string $formRequestClass,
        Ruleset $expectedRules
    ): void {
        // Arrange

        $parameter = $this->makeReflectionParameter([
            'getType' => $formRequestClass,
            'hasType' => true,
        ]);

        $route = $this->makeExtractableRoute([$parameter]);

        // Anticipate

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
    }

    public static function extractProvider(): Generator
    {
        yield 'form request with rules' => [
            'formRequestClass' => FormRequestStub::class,
            'expectedRules' => Ruleset::make([
                'name' => ['required', 'string'],
                'email' => ['required', 'email'],
            ]),
        ];

        yield 'form request with different rules' => [
            'formRequestClass' => FormRequestWithDifferentRulesStub::class,
            'expectedRules' => Ruleset::make([
                'title' => ['required'],
                'content' => ['nullable', 'string'],
            ]),
        ];
    }

    #[RunInSeparateProcess] // <- Having overload Mock here, let's not leak it.
    #[PreserveGlobalState(false)]
    public function test_it_handles_exception_when_calling_rules_method(): void
    {
        // Arrange

        $parameter = $this->makeReflectionParameter([
            'hasType' => true,
            'getType' => FormRequestWithExceptionStub::class,
        ]);

        $route = $this->makeExtractableRoute([$parameter]);

        $visitorMock = Mockery::mock('overload:'.RulesMethodVisitor::class);
        $nodeTraverserMock = Mockery::mock('overload:'.NodeTraverser::class);
        $parserFactoryMock = Mockery::mock('overload:'.ParserFactory::class);
        $parserMock = Mockery::mock('overload:'.Php8::class);

        // Anticipate

        $parserMock->shouldReceive('parse')->andReturn([]);
        $parserFactoryMock->shouldReceive('createForNewestSupportedVersion')->andReturn($parserMock);

        $nodeTraverserMock
            ->shouldReceive('addVisitor')
            ->withAnyArgs()
            ->with(Mockery::type(RulesMethodVisitor::class))
            ->ordered()
            ->once();

        // TODO [Test] This is not a conclusive assert at the moment, it doesn't ensure the AST is extracted correctly.
        $nodeTraverserMock
            ->shouldReceive('traverse')
            ->with(Mockery::type('array'))
            ->ordered()
            ->once();

        $responseSchemaStub = Schema::empty();

        $expectedRuleset = Ruleset::make([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $visitorMock
            ->shouldReceive('getRules')
            ->ordered()
            ->andReturn($expectedRuleset);

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

                $stubRequestClassName = (new ReflectionClass(FormRequestWithExceptionStub::class))->getFileName();

                // Note the error will be coming from `FormRequestWithExceptionStub`.
                $this->assertEquals(
                    <<<HTML
<b>Cannot access request context</b><br />
<small>{$stubRequestClassName}::11</small>
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

    public function test_it_extracts_from_first_form_request_with_multiple_parameters(): void
    {
        // Arrange

        $regularParam = $this->makeReflectionParameter([
            'hasType' => true,
            'getType' => \stdClass::class,
        ]);

        $formRequestParam = $this->makeReflectionParameter([
            'hasType' => true,
            'getType' => FormRequestStub::class,
        ]);

        $route = $this->makeExtractableRoute([$regularParam, $formRequestParam]);

        $expectedRuleset = Ruleset::make([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        // Anticipate

        $responseSchemaStub = new Schema(properties: [
            new StringSchemaProperty(
                name: '::property::',
            ),
        ]);

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

                $this->assertNull($rulesExtractionError);

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
        yield 'no form request parameter' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => \stdClass::class],
            ],
        ];

        yield 'parameter type is not named type' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => null],
            ],
        ];

        yield 'form request without rules method' => [
            'parameterReflectionData' => [
                ['hasType' => true, 'getType' => FormRequestWithoutRulesStub::class],
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
