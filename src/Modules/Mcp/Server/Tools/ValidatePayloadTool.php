<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ValidatePayloadAction;

class ValidatePayloadTool extends NimbusTool
{
    public function __construct(
        private readonly ValidatePayloadAction $validatePayloadAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_validate_payload',
            description: 'Checks a request body against the schema of a route. It does not call the application.',
            properties: [
                'route' => self::stringProperty('A route id from nimbus_list_routes, or a route name.'),
                'body' => self::objectProperty('The request body to check.'),
                'application' => self::applicationProperty(),
            ],
            required: ['route', 'body'],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        $body = $arguments['body'] ?? [];

        return $this->validatePayloadAction->execute(
            route: (string) $arguments['route'],
            body: is_array($body) ? $body : [],
            application: self::readOptionalString(arguments: $arguments, key: 'application'),
        );
    }
}
