<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Illuminate\Support\Str;
use Throwable;

class RulesExtractionError
{
    public function __construct(
        private readonly Throwable $throwable,
    ) {}

    public function toHtml(): string
    {
        $errorMessage = filled($this->throwable->getMessage())
            ? $this->throwable->getMessage()
            : '[no error message]';

        $trace = Str::replace("\n", '<br >', $this->throwable->getTraceAsString());

        return <<<ERROR_HTML
<b>{$errorMessage}</b><br />
<small>{$this->throwable->getFile()}::{$this->throwable->getLine()}</small>
<p class="text-xs">{$trace}</p>
ERROR_HTML;
    }
}
