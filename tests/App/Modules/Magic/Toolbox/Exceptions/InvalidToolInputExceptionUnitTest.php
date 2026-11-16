<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

#[CoversClass(InvalidToolInputException::class)]
class InvalidToolInputExceptionUnitTest extends TestCase
{
    #[DataProvider('reasons')]
    public function test_it_explains_the_reason(InvalidToolInputException $exception, string $message): void
    {
        // Assert

        $this->assertSame($message, $exception->getMessage());
    }

    public static function reasons(): \Generator
    {
        yield 'value cannot be decrypted' => [
            'exception' => InvalidToolInputException::becauseValueCannotBeDecrypted(),
            'message' => 'The value cannot be decrypted with the app key.',
        ];

        yield 'route is unknown' => [
            'exception' => InvalidToolInputException::becauseRouteIsUnknown('users.index'),
            'message' => 'The route `users.index` does not exist.',
        ];

        yield 'url cannot be generated' => [
            'exception' => InvalidToolInputException::becauseUrlCannotBeGenerated('Missing parameter.'),
            'message' => 'Missing parameter.',
        ];

        yield 'token is not a jwt' => [
            'exception' => InvalidToolInputException::becauseTokenIsNotAJwt(),
            'message' => 'The value is not a valid JWT.',
        ];

        yield 'sanctum is missing' => [
            'exception' => InvalidToolInputException::becauseSanctumIsMissing(),
            'message' => 'Laravel Sanctum is not installed.',
        ];

        yield 'token is unknown' => [
            'exception' => InvalidToolInputException::becauseTokenIsUnknown(),
            'message' => 'No Sanctum token matches this value.',
        ];

        yield 'session is unknown' => [
            'exception' => InvalidToolInputException::becauseSessionIsUnknown(),
            'message' => 'No stored session matches this value.',
        ];
    }
}
