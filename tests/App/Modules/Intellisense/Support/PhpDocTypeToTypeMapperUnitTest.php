<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Intellisense\Support;

use Generator;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Intellisense\Support\PhpDocTypeToTypeMapper;

#[CoversClass(PhpDocTypeToTypeMapper::class)]
class PhpDocTypeToTypeMapperUnitTest extends TestCase
{
    #[DataProvider('typesDataProvider')]
    public function test_it_maps_a_phpdoc_type_to_the_same_typescript_type(string $phpDoc, string $expected): void
    {
        // Arrange

        $mapper = new PhpDocTypeToTypeMapper(['Query' => 'InsightQuery']);

        // Act

        $mapped = $mapper->map($this->parse($phpDoc));

        // Assert

        $this->assertSame($expected, $mapped);
    }

    public static function typesDataProvider(): Generator
    {
        yield 'string' => [
            'phpDoc' => 'string',
            'expected' => 'string',
        ];

        yield 'int' => [
            'phpDoc' => 'int',
            'expected' => 'number',
        ];

        yield 'float' => [
            'phpDoc' => 'float',
            'expected' => 'number',
        ];

        yield 'bool' => [
            'phpDoc' => 'bool',
            'expected' => 'boolean',
        ];

        yield 'true' => [
            'phpDoc' => 'true',
            'expected' => 'boolean',
        ];

        yield 'null' => [
            'phpDoc' => 'null',
            'expected' => 'null',
        ];

        yield 'string literal' => [
            'phpDoc' => "'hit'",
            'expected' => "'hit'",
        ];

        yield 'union of string literals' => [
            'phpDoc' => "'hit'|'miss'|'write'",
            'expected' => "'hit' | 'miss' | 'write'",
        ];

        yield 'mixed' => [
            'phpDoc' => 'mixed',
            'expected' => 'unknown',
        ];

        yield 'nullable' => [
            'phpDoc' => '?string',
            'expected' => 'string | null',
        ];

        yield 'union' => [
            'phpDoc' => 'bool|null',
            'expected' => 'boolean | null',
        ];

        yield 'array suffix' => [
            'phpDoc' => 'string[]',
            'expected' => 'string[]',
        ];

        yield 'list' => [
            'phpDoc' => 'list<string>',
            'expected' => 'string[]',
        ];

        yield 'array with one argument' => [
            'phpDoc' => 'array<string>',
            'expected' => 'string[]',
        ];

        yield 'list of a union' => [
            'phpDoc' => 'list<int|string>',
            'expected' => '(number | string)[]',
        ];

        yield 'list of lists' => [
            'phpDoc' => 'list<list<int>>',
            'expected' => 'number[][]',
        ];

        yield 'array keyed by string' => [
            'phpDoc' => 'array<string, mixed>',
            'expected' => 'Record<string, unknown>',
        ];

        yield 'array keyed by int' => [
            'phpDoc' => 'array<int, string>',
            'expected' => 'Record<number, string>',
        ];

        yield 'array keyed by array-key' => [
            'phpDoc' => 'array<array-key, string>',
            'expected' => 'Record<string, string>',
        ];

        yield 'alias' => [
            'phpDoc' => 'Query',
            'expected' => 'InsightQuery',
        ];

        yield 'list of aliases' => [
            'phpDoc' => 'list<Query>',
            'expected' => 'InsightQuery[]',
        ];

        yield 'array shape' => [
            'phpDoc' => 'array{sql: string, time_ms: float}',
            'expected' => '{ sql: string; time_ms: number }',
        ];

        yield 'array shape with an optional item' => [
            'phpDoc' => 'array{sql: string, time_ms?: float}',
            'expected' => '{ sql: string; time_ms?: number }',
        ];

        yield 'array shape with a quoted key' => [
            'phpDoc' => "array{'a-b': int}",
            'expected' => '{ a-b: number }',
        ];

        yield 'nested array shape' => [
            'phpDoc' => 'array{dropped: array{queries: int}}',
            'expected' => '{ dropped: { queries: number } }',
        ];
    }

    #[DataProvider('unsupportedTypesDataProvider')]
    public function test_it_refuses_a_type_it_cannot_map(string $phpDoc, string $message): void
    {
        // Arrange

        $mapper = new PhpDocTypeToTypeMapper;

        // Assert

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        // Act

        $mapper->map($this->parse($phpDoc));
    }

    public static function unsupportedTypesDataProvider(): Generator
    {
        yield 'unknown class or alias' => [
            'phpDoc' => 'Unknown',
            'message' => 'Unknown PHPDoc type Unknown',
        ];

        yield 'callable' => [
            'phpDoc' => 'callable',
            'message' => 'Unknown PHPDoc type callable',
        ];

        yield 'generic with a name it does not know' => [
            'phpDoc' => 'Collection<int, string>',
            'message' => 'Unsupported PHPDoc type',
        ];

        yield 'array shape without names' => [
            'phpDoc' => 'array{int, string}',
            'message' => 'An array shape item needs a name.',
        ];
    }

    public function test_it_lists_the_members_of_an_array_shape(): void
    {
        // Arrange

        $mapper = new PhpDocTypeToTypeMapper;

        $shape = $this->parseShape('array{a: int, b?: list<string>}');

        // Act

        $members = $mapper->members($shape);

        // Assert

        $this->assertSame(['a: number', 'b?: string[]'], $members);
    }

    private function parseShape(string $type): ArrayShapeNode
    {
        $shape = $this->parse($type);

        return $shape instanceof ArrayShapeNode ? $shape : throw new RuntimeException('Expected an array shape.');
    }

    private function parse(string $type): TypeNode
    {
        $config = new ParserConfig([]);

        $typeParser = new TypeParser($config, new ConstExprParser($config));

        return $typeParser->parse(new TokenIterator((new Lexer($config))->tokenize($type)));
    }
}
