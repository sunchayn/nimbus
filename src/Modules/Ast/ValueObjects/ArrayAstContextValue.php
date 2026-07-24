<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\ValueObjects;

use Sunchayn\Nimbus\Modules\Ast\Contracts\AstContextValueContract;

/** @final */
readonly class ArrayAstContextValue implements AstContextValueContract
{
    /**
     * @param  array<array-key, mixed>  $value
     */
    public function __construct(
        private array $value,
        private ?string $variableName = null,
    ) {}

    public function getVariableName(): ?string
    {
        return $this->variableName;
    }

    /**
     * @return mixed[]
     */
    public function getValue(): array
    {
        return $this->value;
    }
}
