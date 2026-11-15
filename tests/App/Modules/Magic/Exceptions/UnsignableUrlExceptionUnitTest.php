<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\Exceptions\UnsignableUrlException;

#[CoversClass(UnsignableUrlException::class)]
class UnsignableUrlExceptionUnitTest extends TestCase
{
    #[DataProvider('reasons')]
    public function test_it_explains_the_reason(UnsignableUrlException $exception, string $message): void
    {
        // Assert

        $this->assertSame($message, $exception->getMessage());
    }

    public static function reasons(): \Generator
    {
        yield 'no route' => [
            'exception' => UnsignableUrlException::noRoute(),
            'message' => 'The URL does not match a route of this application.',
        ];

        yield 'not signed' => [
            'exception' => UnsignableUrlException::notSigned(),
            'message' => 'The route does not use the signed middleware.',
        ];

        yield 'unnamed' => [
            'exception' => UnsignableUrlException::unnamed(),
            'message' => 'The route has no name, and a signed URL is built from the route name.',
        ];
    }
}
