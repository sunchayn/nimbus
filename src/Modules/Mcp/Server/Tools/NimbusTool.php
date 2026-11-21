<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;
use Mcp\Server\Exception\ToolCallException;
use Mcp\Server\Handler\ToolHandlerInterface;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/**
 * Shared error handling of the Nimbus tools.
 * Expected failures reach the agent as a structured error, not as a crash.
 */
abstract class NimbusTool implements ToolHandlerInterface
{
    /**
     * The name, description, input schema, and annotations the agent sees.
     */
    abstract public function definition(): Tool;

    /**
     * Runs the tool and returns its structured result.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     *
     * @throws McpToolException
     * @throws WorkspaceException
     * @throws MisconfiguredValueException
     * @throws RouteNotFoundException
     * @throws RoutesProcessingException
     */
    abstract protected function handle(array $arguments): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     *
     * @throws ToolCallException
     */
    public function execute(array $arguments, ClientGateway $gateway): array
    {
        $arguments = $this->toPlainArrays(arguments: $arguments);

        try {
            $result = $this->handle(arguments: $arguments);
        } catch (McpToolException|WorkspaceException|MisconfiguredValueException|RouteNotFoundException|RoutesProcessingException $exception) {
            $this->failWith(toolException: $this->toToolException(exception: $exception));
        }

        return $result;
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @param  list<string>  $required
     */
    protected static function defineTool(
        string $name,
        string $description,
        array $properties,
        array $required = [],
        ?ToolAnnotations $annotations = null,
    ): Tool {
        $inputSchema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $inputSchema['required'] = $required;
        }

        return new Tool(
            name: $name,
            title: null,
            inputSchema: $inputSchema,
            description: $description,
            annotations: $annotations,
        );
    }

    /**
     * @param  list<string>|null  $enum
     * @return array<string, mixed>
     */
    protected static function stringProperty(string $description, ?array $enum = null): array
    {
        return array_filter(
            [
                'type' => 'string',
                'enum' => $enum,
                'description' => $description,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function applicationProperty(): array
    {
        return self::stringProperty('An application key from nimbus_list_applications. Leave it out to search every application. Pass it when a route exists in more than one.');
    }

    /**
     * @return array<string, mixed>
     */
    protected static function objectProperty(string $description): array
    {
        return [
            'type' => 'object',
            'description' => $description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function integerProperty(string $description): array
    {
        return [
            'type' => 'integer',
            'description' => $description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function bodyProperty(): array
    {
        return [
            'type' => ['object', 'string'],
            'description' => 'The request body. Use an object for JSON, or a string for plain text.',
        ];
    }

    protected static function readOnly(): ToolAnnotations
    {
        return new ToolAnnotations(readOnlyHint: true);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected static function readOptionalString(array $arguments, string $key): ?string
    {
        $value = $arguments[$key] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<array-key, mixed>|string
     */
    protected static function readBody(array $arguments): array|string
    {
        $body = $arguments['body'] ?? [];

        return is_array($body) || is_string($body) ? $body : [];
    }

    /**
     * The SDK validates the arguments against the input schema and decodes nested objects as stdClass.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function toPlainArrays(array $arguments): array
    {
        return (array) json_decode(
            json: (string) json_encode($arguments),
            associative: true,
        );
    }

    private function toToolException(
        McpToolException|WorkspaceException|MisconfiguredValueException|RouteNotFoundException|RoutesProcessingException $exception,
    ): McpToolException {
        return match (true) {
            $exception instanceof WorkspaceException => McpToolException::becauseInputIsInvalid(
                message: $exception->getMessage(),
                hint: 'Turn the workspace on in config/nimbus.php, then try again.',
            ),
            $exception instanceof MisconfiguredValueException => McpToolException::becauseInputIsInvalid(
                message: $exception->getMessage(),
                hint: 'Fix the Nimbus config, then try again.',
            ),
            $exception instanceof RouteNotFoundException => McpToolException::becauseRouteIsNotFound(
                reference: $exception->reference,
                suggestions: $exception->suggestions,
            ),
            $exception instanceof RoutesProcessingException => McpToolException::becauseRoutesAreUnavailable(reason: strip_tags($exception->getMessage())),
            default => $exception,
        };
    }

    /**
     * @throws ToolCallException
     */
    private function failWith(McpToolException $toolException): never
    {
        throw new ToolCallException(
            message: (string) json_encode($toolException->toArray()),
            code: 0,
            previous: $toolException,
        );
    }
}
