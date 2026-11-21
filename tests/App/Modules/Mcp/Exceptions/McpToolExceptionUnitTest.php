<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Exceptions;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;

#[CoversClass(McpToolException::class)]
class McpToolExceptionUnitTest extends TestCase
{
    public function test_it_converts_to_the_error_shape_of_the_agent(): void
    {
        // Arrange

        $exception = McpToolException::becauseInputIsInvalid(
            message: 'Bad input.',
            hint: 'Fix it.',
        );

        // Act

        $error = $exception->toArray();

        // Assert

        $this->assertSame(
            [
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => 'Bad input.',
                    'hint' => 'Fix it.',
                ],
            ],
            $error,
        );
    }

    public function test_it_suggests_the_nearest_routes(): void
    {
        // Arrange

        $exception = McpToolException::becauseRouteIsNotFound(
            reference: 'POST api/user',
            suggestions: ['POST api/users'],
        );

        // Act

        $hint = $exception->hint;

        // Assert

        $this->assertSame(
            'Did you mean `POST api/users`?',
            $hint,
        );
    }

    public function test_it_points_to_the_route_list_without_suggestions(): void
    {
        // Arrange

        $exception = McpToolException::becauseRouteIsNotFound(
            reference: 'GET nowhere',
            suggestions: [],
        );

        // Act

        $hint = $exception->hint;

        // Assert

        $this->assertStringContainsString(
            'nimbus_list_routes',
            $hint,
        );
    }

    public function test_it_suggests_several_routes(): void
    {
        // Arrange

        $exception = McpToolException::becauseRouteIsNotFound(
            reference: 'POST api/user',
            suggestions: ['POST api/users', 'GET api/users'],
        );

        // Act

        $hint = $exception->hint;

        // Assert

        $this->assertSame(
            'Did you mean `POST api/users`, `GET api/users`?',
            $hint,
        );
    }

    #[DataProvider('factories')]
    public function test_it_builds_each_failure_with_its_code_message_and_hint(
        callable $factory,
        string $code,
        string $message,
        string $hint,
    ): void {
        // Arrange

        $exception = $factory();

        // Act

        $error = $exception->toArray();

        // Assert

        $this->assertSame($code, $exception->errorCode);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($hint, $exception->hint);
        $this->assertSame(
            ['error' => ['code' => $code, 'message' => $message, 'hint' => $hint]],
            $error,
        );
    }

    /**
     * @return Generator<string, array{factory: callable, code: string, message: string, hint: string}>
     */
    public static function factories(): Generator
    {
        yield 'route is not found' => [
            'factory' => fn (): McpToolException => McpToolException::becauseRouteIsNotFound('GET nowhere', []),
            'code' => McpToolException::ROUTE_NOT_FOUND,
            'message' => 'No route matches `GET nowhere`.',
            'hint' => 'Call nimbus_list_routes to find a route id.',
        ];

        yield 'route is ambiguous' => [
            'factory' => fn (): McpToolException => McpToolException::becauseRouteIsAmbiguous('GET api/users', ['admin', 'public']),
            'code' => McpToolException::ROUTE_AMBIGUOUS,
            'message' => '`GET api/users` matches routes in several applications, `admin`, `public`.',
            'hint' => 'Pass `application` with one of them.',
        ];

        yield 'application is unknown' => [
            'factory' => fn (): McpToolException => McpToolException::becauseApplicationIsUnknown('missing', ['admin', 'public']),
            'code' => McpToolException::INVALID_INPUT,
            'message' => 'The application `missing` is not configured.',
            'hint' => 'Call nimbus_list_applications. The keys are `admin`, `public`.',
        ];

        yield 'routes are unavailable' => [
            'factory' => fn (): McpToolException => McpToolException::becauseRoutesAreUnavailable('Parse error.'),
            'code' => McpToolException::ROUTES_UNAVAILABLE,
            'message' => 'Parse error.',
            'hint' => 'Fix the routes error in the Nimbus UI or the config, then try again.',
        ];

        yield 'input is invalid' => [
            'factory' => fn (): McpToolException => McpToolException::becauseInputIsInvalid('Bad input.', 'Fix it.'),
            'code' => McpToolException::INVALID_INPUT,
            'message' => 'Bad input.',
            'hint' => 'Fix it.',
        ];

        yield 'decryption failed' => [
            'factory' => fn (): McpToolException => McpToolException::becauseDecryptionFailed(),
            'code' => McpToolException::DECRYPTION_FAILED,
            'message' => 'The value could not be decrypted.',
            'hint' => 'Check that the value was encrypted by this application and that the kind is correct.',
        ];

        yield 'actor is not found' => [
            'factory' => fn (): McpToolException => McpToolException::becauseActorIsNotFound('web', '42'),
            'code' => McpToolException::ACTOR_NOT_FOUND,
            'message' => 'No user with the key `42` exists for the `web` guard.',
            'hint' => 'Use the primary key of an existing user.',
        ];

        yield 'environment is not allowed' => [
            'factory' => fn (): McpToolException => McpToolException::becauseEnvironmentIsNotAllowed('Not allowed.', 'Pick another one.'),
            'code' => McpToolException::ENVIRONMENT_NOT_ALLOWED,
            'message' => 'Not allowed.',
            'hint' => 'Pick another one.',
        ];

        yield 'rate is limited' => [
            'factory' => fn (): McpToolException => McpToolException::becauseRateIsLimited(30),
            'code' => McpToolException::RATE_LIMITED,
            'message' => 'Too many send requests in the last minute.',
            'hint' => 'Wait 30 seconds, or raise `nimbus.mcp.rate_limit`.',
        ];

        yield 'sent request is not found' => [
            'factory' => fn (): McpToolException => McpToolException::becauseSentRequestIsNotFound('abc'),
            'code' => McpToolException::SENT_REQUEST_NOT_FOUND,
            'message' => 'No sent request has the id `abc`.',
            'hint' => 'Only the latest requests are kept. Send the request again with nimbus_send_request.',
        ];

        yield 'request failed' => [
            'factory' => fn (): McpToolException => McpToolException::becauseRequestFailed('Boot failed.'),
            'code' => McpToolException::REQUEST_FAILED,
            'message' => 'Boot failed.',
            'hint' => 'Check that the application boots and that the driver in `nimbus.mcp.driver` can reach it.',
        ];
    }
}
