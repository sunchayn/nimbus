<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Services\Uri;

interface UriContract
{
    public function getVersion(): string;

    public function getResource(): string;
}
