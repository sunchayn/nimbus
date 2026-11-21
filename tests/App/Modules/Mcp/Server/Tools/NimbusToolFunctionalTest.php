<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use Generator;
use Mcp\Server\ClientGateway;
use Mcp\Server\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\NimbusTool;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools\Stubs\StubNimbusTool;
use Sunchayn\Nimbus\Tests\TestCase;
use Throwable;

#[CoversClass(NimbusTool::class)]
class NimbusToolFunctionalTest extends TestCase
{
    public function test_it_builds_the_definition_with_the_required_fields_and_the_annotations(): void
    {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'stub_tool',
            $definition->name,
        );

        $this->assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'A name.',
                    ],
                ],
                'required' => ['name'],
            ],
            $definition->inputSchema,
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }

    public function test_it_describes_the_properties(): void
    {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        // Act

        $properties = $tool->listProperties();

        // Assert

        $this->assertSame(
            [
                'type' => 'string',
                'description' => 'A plain string.',
            ],
            $properties['plain'],
        );

        $this->assertSame(
            [
                'type' => 'string',
                'enum' => [
                    'one',
                    'two',
                ],
                'description' => 'A string from a list.',
            ],
            $properties['enum'],
        );

        $this->assertSame(
            'string',
            $properties['application']['type'],
        );

        $this->assertSame(
            [
                'type' => 'object',
                'description' => 'An object.',
            ],
            $properties['object'],
        );

        $this->assertSame(
            [
                'type' => 'integer',
                'description' => 'An integer.',
            ],
            $properties['integer'],
        );

        $this->assertSame(
            [
                'object',
                'string',
            ],
            $properties['body']['type'],
        );
    }

    public function test_it_returns_the_result_of_the_handler(): void
    {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        // Act

        $result = $tool->execute(
            arguments: ['name' => 'Ada'],
            gateway: $this->createStub(ClientGateway::class),
        );

        // Assert

        $this->assertSame(
            ['name' => 'Ada'],
            $result,
        );
    }

    public function test_it_converts_nested_objects_to_arrays(): void
    {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        // Act

        $result = $tool->execute(
            arguments: [
                'user' => (object) [
                    'tags' => ['a'],
                    'address' => (object) ['city' => 'Tunis'],
                ],
            ],
            gateway: $this->createStub(ClientGateway::class),
        );

        // Assert

        $this->assertSame(
            [
                'user' => [
                    'tags' => ['a'],
                    'address' => ['city' => 'Tunis'],
                ],
            ],
            $result,
        );
    }

    #[DataProvider('failures')]
    public function test_it_reports_an_expected_failure_as_a_structured_error(
        Throwable $failure,
        McpToolException $expected,
    ): void {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        $tool->failure = $failure;

        // Act

        $exception = $this->catchFailure(tool: $tool);

        // Assert

        $this->assertInstanceOf(
            ToolCallException::class,
            $exception,
        );

        $this->assertSame(
            json_encode($expected->toArray()),
            $exception->getMessage(),
        );

        $this->assertInstanceOf(
            McpToolException::class,
            $exception->getPrevious(),
        );
    }

    public static function failures(): Generator
    {
        yield 'a tool exception' => [
            'failure' => McpToolException::becauseRateIsLimited(seconds: 30),
            'expected' => McpToolException::becauseRateIsLimited(seconds: 30),
        ];

        yield 'a workspace failure' => [
            'failure' => WorkspaceException::disabled(),
            'expected' => McpToolException::becauseInputIsInvalid(
                message: 'The workspace is disabled.',
                hint: 'Turn the workspace on in config/nimbus.php, then try again.',
            ),
        ];

        yield 'a misconfigured value' => [
            'failure' => MisconfiguredValueException::becauseRouteProcessingStrategyIsInvalid('nope'),
            'expected' => McpToolException::becauseInputIsInvalid(
                message: 'The configured route processing strategy `nope` is invalid.',
                hint: 'Fix the Nimbus config, then try again.',
            ),
        ];

        yield 'a missing route' => [
            'failure' => RouteNotFoundException::becauseNoRouteMatches(
                reference: 'GET nowhere',
                suggestions: ['GET api/users'],
            ),
            'expected' => McpToolException::becauseRouteIsNotFound(
                reference: 'GET nowhere',
                suggestions: ['GET api/users'],
            ),
        ];

        yield 'a routes processing failure' => [
            'failure' => new class('<b>The routes are broken.</b>') extends RuntimeException implements RoutesProcessingException
            {
                public function getFrontEndIdentifier(): string
                {
                    return 'broken';
                }

                public function toArray(): array
                {
                    return [];
                }
            },
            'expected' => McpToolException::becauseRoutesAreUnavailable(reason: 'The routes are broken.'),
        ];
    }

    public function test_it_does_not_catch_an_unexpected_failure(): void
    {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        $tool->failure = new RuntimeException('Unexpected.');

        // Act

        $exception = $this->catchFailure(tool: $tool);

        // Assert

        $this->assertInstanceOf(
            RuntimeException::class,
            $exception,
        );

        $this->assertSame(
            'Unexpected.',
            $exception->getMessage(),
        );
    }

    public function test_it_reads_an_optional_string(): void
    {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        // Act

        $presentValue = $tool->readString(
            arguments: ['key' => 12],
            key: 'key',
        );

        $missingValue = $tool->readString(
            arguments: [],
            key: 'key',
        );

        // Assert

        $this->assertSame(
            '12',
            $presentValue,
        );

        $this->assertNull($missingValue);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<array-key, mixed>|string  $expected
     */
    #[DataProvider('bodies')]
    public function test_it_reads_the_body(
        array $arguments,
        array|string $expected,
    ): void {
        // Arrange

        $tool = resolve(StubNimbusTool::class);

        // Act

        $body = $tool->readBodyOf(arguments: $arguments);

        // Assert

        $this->assertSame(
            $expected,
            $body,
        );
    }

    public static function bodies(): Generator
    {
        yield 'an object body' => [
            'arguments' => ['body' => ['name' => 'Ada']],
            'expected' => ['name' => 'Ada'],
        ];

        yield 'a string body' => [
            'arguments' => ['body' => 'plain'],
            'expected' => 'plain',
        ];

        yield 'a missing body' => [
            'arguments' => [],
            'expected' => [],
        ];

        yield 'an unsupported body' => [
            'arguments' => ['body' => 12],
            'expected' => [],
        ];
    }

    /*
     * Helpers.
     */

    private function catchFailure(StubNimbusTool $tool): Throwable
    {
        try {
            $tool->execute(
                arguments: [],
                gateway: $this->createStub(ClientGateway::class),
            );
        } catch (Throwable $throwable) {
            return $throwable;
        }

        $this->fail('The tool did not fail.');
    }
}
