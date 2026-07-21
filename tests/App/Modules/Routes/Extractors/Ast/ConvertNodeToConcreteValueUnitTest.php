<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Ast;

use Generator;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Ast\ConvertNodeToConcreteValue;

#[CoversClass(ConvertNodeToConcreteValue::class)]
class ConvertNodeToConcreteValueUnitTest extends TestCase
{
    #[DataProvider('relativeClassKeywordScenariosDataProvider')]
    public function test_it_skips_relative_class_keywords_without_throwing(
        string $phpCode,
    ): void {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($phpCode);

        // `$x = <expr>;` → expression statement → assign → right-hand side
        $node = $ast[0]->expr->expr;

        // Act

        $result = ConvertNodeToConcreteValue::process($node);

        // Assert

        $this->assertNull($result);
    }

    public static function relativeClassKeywordScenariosDataProvider(): Generator
    {
        yield 'static call' => [
            'phpCode' => '<?php $x = static::getEloquentQuery();',
        ];

        yield 'self call' => [
            'phpCode' => '<?php $x = self::getEloquentQuery();',
        ];

        yield 'parent call' => [
            'phpCode' => '<?php $x = parent::getEloquentQuery();',
        ];

        yield 'new static' => [
            'phpCode' => '<?php $x = new static();',
        ];

        yield 'new self' => [
            'phpCode' => '<?php $x = new self();',
        ];
    }

    public function test_it_still_resolves_concrete_static_calls(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse('<?php $x = \Illuminate\Support\Str::upper("hi");');
        $node = $ast[0]->expr->expr;

        // Act

        $result = ConvertNodeToConcreteValue::process($node);

        // Assert

        $this->assertSame('HI', $result);
    }

    public function test_it_skips_non_callable_static_methods_without_throwing(): void
    {
        // Arrange — use a concrete class without __callStatic so is_callable is false

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse('<?php $x = \stdClass::thisMethodDoesNotExist();');
        $node = $ast[0]->expr->expr;

        // Act

        $result = ConvertNodeToConcreteValue::process($node);

        // Assert

        $this->assertNull($result);
    }
}
