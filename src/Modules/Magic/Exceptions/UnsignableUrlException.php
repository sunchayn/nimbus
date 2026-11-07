<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Exceptions;

use RuntimeException;

class UnsignableUrlException extends RuntimeException
{
    public static function noRoute(): self
    {
        return new self('The URL does not match a route of this application.');
    }

    public static function notSigned(): self
    {
        return new self('The route does not use the signed middleware.');
    }

    public static function unnamed(): self
    {
        return new self('The route has no name, and a signed URL is built from the route name.');
    }
}
