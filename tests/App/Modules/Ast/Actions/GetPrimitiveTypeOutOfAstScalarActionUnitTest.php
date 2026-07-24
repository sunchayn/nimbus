<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetPhpTypeFromAstScalarAction;

#[CoversClass(GetPhpTypeFromAstScalarAction::class)]
class GetPrimitiveTypeOutOfAstScalarActionUnitTest extends TestCase
{
    #[DataProvider('astTypeProvider')]
    public function test_it_resolves_primitive_types_from_ast_nodes(
        Node|Expr $expr,
        ?string $expectedType
    ): void {
        // Arrange

        $action = new GetPhpTypeFromAstScalarAction;

        // Act

        $actual = $action->execute($expr);

        // Assert

        $this->assertSame($expectedType, $actual);
    }

    public static function astTypeProvider(): Generator
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        yield 'string literal' => [
            'expr' => $parser->parse('<?php "hello";')[0]->expr,
            'expectedType' => 'string',
        ];

        yield 'integer literal' => [
            'expr' => $parser->parse('<?php 42;')[0]->expr,
            'expectedType' => 'integer',
        ];

        yield 'float literal' => [
            'expr' => $parser->parse('<?php 3.14;')[0]->expr,
            'expectedType' => 'number',
        ];

        yield 'boolean true' => [
            'expr' => $parser->parse('<?php true;')[0]->expr,
            'expectedType' => 'boolean',
        ];

        yield 'boolean false' => [
            'expr' => $parser->parse('<?php FALSE;')[0]->expr,
            'expectedType' => 'boolean',
        ];

        yield 'variable returns null' => [
            'expr' => new Variable('x'),
            'expectedType' => null,
        ];

        yield 'array literal returns null' => [
            'expr' => $parser->parse('<?php [1, 2, 3];')[0]->expr,
            'expectedType' => null,
        ];

        yield 'other const fetch returns null' => [
            'expr' => $parser->parse('<?php PHP_VERSION;')[0]->expr,
            'expectedType' => null,
        ];
    }
}
