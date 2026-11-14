<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Intellisense\Support;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use RuntimeException;

/**
 * Turns a parsed PHPDoc type into the TypeScript type with the same meaning.
 */
class PhpDocTypeToTypeMapper
{
    /**
     * @param  array<string, string>  $aliases  TypeScript names by PHPDoc alias name.
     */
    public function __construct(private readonly array $aliases = []) {}

    public function map(TypeNode $type): string
    {
        return match (true) {
            $type instanceof IdentifierTypeNode => $this->identifier($type->name),
            $type instanceof NullableTypeNode => $this->map($type->type).' | null',
            $type instanceof UnionTypeNode => implode(' | ', array_map($this->map(...), $type->types)),
            $type instanceof ArrayTypeNode => $this->list($type->type),
            $type instanceof GenericTypeNode => $this->generic($type),
            $type instanceof ArrayShapeNode => '{ '.implode('; ', $this->members($type)).' }',
            $type instanceof ConstTypeNode && $type->constExpr instanceof ConstExprStringNode => (string) $type->constExpr,
            default => throw new RuntimeException('Unsupported PHPDoc type '.$type),
        };
    }

    /**
     * Returns one `key: type` entry for each item of an array shape, with a `?` after the key of an optional item.
     *
     * @return list<string>
     */
    public function members(ArrayShapeNode $shape): array
    {
        $members = [];

        foreach ($shape->items as $item) {
            $members[] = $this->keyName($item->keyName).($item->optional ? '?' : '').': '.$this->map($item->valueType);
        }

        return $members;
    }

    private function identifier(string $name): string
    {
        return match ($name) {
            'string' => 'string',
            'int', 'float' => 'number',
            'bool', 'true', 'false' => 'boolean',
            'null' => 'null',
            'mixed' => 'unknown',
            default => $this->aliases[$name] ?? throw new RuntimeException("Unknown PHPDoc type {$name}"),
        };
    }

    private function list(TypeNode $element): string
    {
        $mapped = $this->map($element);

        return str_contains($mapped, ' | ') ? "({$mapped})[]" : "{$mapped}[]";
    }

    private function generic(GenericTypeNode $type): string
    {
        $name = (string) $type->type;
        $arguments = $type->genericTypes;

        if (in_array($name, ['list', 'array'], true) && count($arguments) === 1) {
            return $this->list($arguments[0]);
        }

        if ($name === 'array' && count($arguments) === 2) {
            return sprintf(
                'Record<%s, %s>',
                $this->isInteger($arguments[0]) ? 'number' : 'string',
                $this->map($arguments[1]),
            );
        }

        throw new RuntimeException('Unsupported PHPDoc type '.$type);
    }

    private function isInteger(TypeNode $type): bool
    {
        return $type instanceof IdentifierTypeNode && $type->name === 'int';
    }

    private function keyName(?Node $key): string
    {
        return match (true) {
            $key instanceof IdentifierTypeNode => $key->name,
            $key instanceof ConstExprStringNode => trim($key->value, '\'"'),
            default => throw new RuntimeException('An array shape item needs a name.'),
        };
    }
}
