<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Intellisense;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Intellisense\Contracts\IntellisenseContract;
use Sunchayn\Nimbus\Modules\Intellisense\Support\PhpDocTypeToTypeMapper;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\InsightCollectorResult;

/**
 * Generates the TypeScript interfaces of the insight data that `InsightData::toArray` returns.
 *
 * The types are read from the `@phpstan-type` aliases of the result classes and of `InsightData`, and from the `toArray` return type.
 */
class InsightIntellisense implements IntellisenseContract
{
    private const PREFIX = 'Insight';

    public function getTargetFileName(): string
    {
        return 'insight.ts';
    }

    public function generate(): string
    {
        $declared = [];

        /** @var list<class-string> $classes */
        $classes = [...$this->resultClasses(), InsightData::class];

        foreach ($classes as $class) {
            foreach ($this->parse(new ReflectionClass($class))->getTypeAliasTagValues() as $alias) {
                $declared[] = [$class, $alias];
            }
        }

        $aliases = [];

        foreach ($declared as [, $alias]) {
            $aliases[$alias->alias] = self::PREFIX.$alias->alias;
        }

        $mapper = new PhpDocTypeToTypeMapper($aliases);
        $interfaces = [];

        foreach ($declared as [, $alias]) {
            $interfaces[] = $this->declaration($aliases[$alias->alias], $alias->type, $mapper);
        }

        $interfaces[] = $this->declaration(self::PREFIX, $this->contractShape(), $mapper);

        return implode("\n\n", $interfaces)."\n";
    }

    /**
     * @return list<class-string<InsightCollectorResult>>
     */
    private function resultClasses(): array
    {
        $classes = [];

        foreach ((new ReflectionClass(InsightData::class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), InsightCollectorResult::class)) {
                $classes[] = $type->getName();
            }
        }

        return $classes;
    }

    private function contractShape(): ArrayShapeNode
    {
        $return = $this->parse(new ReflectionMethod(InsightData::class, 'toArray'))->getReturnTagValues()[0] ?? null;

        return $return?->type instanceof ArrayShapeNode
            ? $return->type
            : throw new RuntimeException('InsightData::toArray needs an array shape as its return type.');
    }

    private function declaration(string $name, TypeNode $type, PhpDocTypeToTypeMapper $mapper): string
    {
        return $type instanceof ArrayShapeNode
            ? $this->interface($name, $mapper->members($type))
            : sprintf('export type %s = %s;', $name, $mapper->map($type));
    }

    /**
     * @param  list<string>  $members
     */
    private function interface(string $name, array $members): string
    {
        $lines = array_map(fn (string $member): string => "    {$member};", $members);

        return "export interface {$name} {\n".implode("\n", $lines)."\n}";
    }

    /**
     * @param  ReflectionClass<covariant object>|ReflectionMethod  $reflection
     */
    private function parse(ReflectionClass|ReflectionMethod $reflection): PhpDocNode
    {
        $config = new ParserConfig([]);
        $constExprParser = new ConstExprParser($config);
        $parser = new PhpDocParser($config, new TypeParser($config, $constExprParser), $constExprParser);

        return $parser->parse(new TokenIterator((new Lexer($config))->tokenize($reflection->getDocComment() ?: '/** */')));
    }
}
