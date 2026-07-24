<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\ValueObjects;

use Sunchayn\Nimbus\Modules\Ast\Contracts\AstContextValueContract;

/**
 * Value object encapsulating AST variables context without instantiating runtime classes.
 *
 * @final
 */
readonly class VariablesContext
{
    /**
     * @var array<string, AstContextValueContract>
     */
    public array $items;

    /**
     * @param  AstContextValueContract[]  $items
     */
    public function __construct(array $items = [])
    {
        $indexedItems = [];

        foreach ($items as $item) {
            if ($item->getVariableName() === null) {
                continue;
            }

            $indexedItems[$item->getVariableName()] = $item;
        }

        $this->items = $indexedItems;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function get(string $name): ?AstContextValueContract
    {
        return $this->items[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->items);
    }

    public function add(AstContextValueContract $variable): self
    {
        $items = $this->items;
        $items[$variable->getVariableName()] = $variable;

        return new self(array_values($items));
    }

    /**
     * @return array<string, AstContextValueContract>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Converts variables back into an associative array of raw values or class names.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_map(
            fn (\Sunchayn\Nimbus\Modules\Ast\Contracts\AstContextValueContract $variable): mixed => $variable->getValue(),
            $this->items,
        );
    }
}
