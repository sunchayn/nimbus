<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\ValueObjects;

use Sunchayn\Nimbus\Modules\Ast\Contracts\AstContextValueContract;

/** @final */
readonly class ObjectAstContextValue implements AstContextValueContract
{
    /**
     * @param  class-string|string  $className
     */
    public function __construct(
        private string $className,
        private ?string $variableName = null,
    ) {}

    public function getVariableName(): ?string
    {
        return $this->variableName;
    }

    public function getValue(): string
    {
        return $this->className;
    }
}
