<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors;

use Generator;
use Illuminate\Support\Arr;
use Mockery;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Ast\ValidateCallVisitor;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Strategies\InlineRequestValidatorExtractorStrategy;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(InlineRequestValidatorExtractorStrategy::class)]
class InlineRequestValidatorExtractorStrategyUnitTest extends TestCase
{
    private SchemaBuilder&Mockery\MockInterface $schemaBuilderMock;

    private InlineRequestValidatorExtractorStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaBuilderMock = Mockery::mock(SchemaBuilder::class);

        $this->strategy = new InlineRequestValidatorExtractorStrategy($this->schemaBuilderMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[DataProvider('matchesProvider')]
    public function test_it_matches_correctly(
        ?string $methodName,
        bool $expected
    ): void {
        // Arrange

        $route = $this->makeExtractableRoute(
            methodName: $methodName,
            codeParser: fn () => []
        );

        // Act

        $actual = $this->strategy->matches($route);

        // Assert

        $this->assertEquals($expected, $actual);
    }

    public static function matchesProvider(): Generator
    {
        yield 'route with method name' => [
            'methodName' => 'store',
            'expected' => true,
        ];

        yield 'route with different method name' => [
            'methodName' => 'update',
            'expected' => true,
        ];

        yield 'route without method name' => [
            'methodName' => null,
            'expected' => false,
        ];
    }

    #[RunInSeparateProcess] // <- Having overload Mock here, let's not leak it.
    #[PreserveGlobalState(false)]
    public function test_it_extracts_schema_from_inline_validation(): void
    {
        // Arrange

        $responseSchemaStub = new Schema(properties: [
            new StringSchemaProperty(name: 'name'),
            new StringSchemaProperty(name: 'email'),
        ]);

        $ast = ['node' => 'value'];

        $methodName = Arr::random(
            [
                'store',
                'get',
                'edit',
            ],
        );

        $route = $this->makeExtractableRoute(
            methodName: $methodName,
            codeParser: fn () => $ast
        );

        $visitorMock = Mockery::mock('overload:'.ValidateCallVisitor::class);
        $nodeTraverserMock = Mockery::mock('overload:'.NodeTraverser::class);

        // Anticipate

        $visitorMock
            ->shouldReceive('__construct')
            ->once()
            ->withArgs(function ($methodArg) use ($methodName) {
                $this->assertEquals(
                    $methodName,
                    $methodArg,
                );

                return true;
            });

        $nodeTraverserMock->shouldReceive('addVisitor')
            ->with(Mockery::type(ValidateCallVisitor::class))
            ->ordered()
            ->once();

        $nodeTraverserMock->shouldReceive('traverse')->with($ast)->ordered()->once();

        $expectedRuleset = Ruleset::fromLaravelRules([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        $visitorMock->shouldReceive('getRules')->ordered()->andReturn($expectedRuleset);

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
            ->withArgs(function (Ruleset $ruleset, ?RulesExtractionError $rulesExtractionError = null) use ($expectedRuleset) {
                $this->assertEquals(
                    $expectedRuleset->all(),
                    $ruleset->all(),
                );

                $this->assertNull($rulesExtractionError);

                return true;
            })
            ->once();
    }

    public function test_it_returns_empty_schema_when_route_does_not_match(): void
    {
        // Arrange

        $route = $this->makeExtractableRoute(
            methodName: null,
            codeParser: fn () => []
        );

        // Anticipate

        $this->schemaBuilderMock->shouldNotReceive('buildSchemaFromRuleset');

        // Act

        $schema = $this->strategy->extract($route);

        // Assert

        $this->assertTrue($schema->isEmpty());
    }

    public function test_it_builds_schema_with_empty_rules_when_no_validation_found(): void
    {
        // Arrange

        $route = $this->makeExtractableRoute(
            methodName: 'store',
            codeParser: fn () => [] // <- Empty AST, no `->validate()` (or other eligible methods) calls.
        );

        $responseSchemaStub = Schema::empty();

        // Anticipate

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
            ->withArgs(function (Ruleset $ruleset, ?RulesExtractionError $rulesExtractionError = null) {
                $this->assertEquals(
                    [],
                    $ruleset->all(),
                );

                $this->assertNull($rulesExtractionError);

                return true;
            })
            ->once();
    }

    /*
     * Generators.
     */

    private function makeExtractableRoute(?string $methodName, callable $codeParser): ExtractableRoute
    {
        return new ExtractableRoute(
            parameters: [], // <- Not needed in this strategy.
            codeParser: $codeParser,
            methodName: $methodName,
        );
    }
}
