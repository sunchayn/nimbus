<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

use Illuminate\Support\Str;
use Throwable;

class RulesExtractionError
{
    // The HTML is rendered once because a throwable cannot be serialized into a routes snapshot.
    private readonly string $html;

    public function __construct(Throwable $throwable)
    {
        $errorMessage = filled($throwable->getMessage())
            ? $throwable->getMessage()
            : '[no error message]';

        $trace = Str::replace("\n", '<br >', $throwable->getTraceAsString());

        $this->html = <<<ERROR_HTML
<b>{$errorMessage}</b><br />
<small>{$throwable->getFile()}::{$throwable->getLine()}</small>
<p class="text-xs">{$trace}</p>
ERROR_HTML;
    }

    public function toHtml(): string
    {
        return $this->html;
    }
}
