<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Contracts;

interface AstContextValueContract
{
    public function getVariableName(): ?string;

    public function getValue(): mixed;
}
