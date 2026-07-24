<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\ValueObjects;

use Sunchayn\Nimbus\Modules\Ast\Contracts\AstContextValueContract;

/** @final */
readonly class ScalarAstContextValue implements AstContextValueContract
{
    public function __construct(
        private string|int|float|bool|null $value,
        private ?string $variableName = null,
    ) {}

    public function getVariableName(): ?string
    {
        return $this->variableName;
    }

    public function getValue(): string|int|float|bool|null
    {
        return $this->value;
    }
}
