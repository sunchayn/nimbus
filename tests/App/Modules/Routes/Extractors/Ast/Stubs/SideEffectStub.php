<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Ast\Stubs;

/**
 * Tracks whether extraction accidentally executed application code.
 */
class SideEffectStub
{
    public static bool $called = false;

    public static function boom(): string
    {
        self::$called = true;

        return 'boom';
    }

    public function __construct()
    {
        self::$called = true;
    }

    public static function reset(): void
    {
        self::$called = false;
    }
}
