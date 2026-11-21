<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\BuildPayloadAction;

class BuildPayloadTool extends NimbusTool
{
    public function __construct(
        private readonly BuildPayloadAction $buildPayloadAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_build_payload',
            description: 'Builds a sample request body for a route from its schema. It does not call the application.',
            properties: [
                'route' => self::stringProperty('A route id from nimbus_list_routes, or a route name.'),
                'mode' => self::stringProperty(
                    description: '`valid` fills every field, `minimal` fills required fields only, `invalid` breaks one rule.',
                    enum: BuildPayloadAction::MODES,
                ),
                'overrides' => self::objectProperty('Values to set. A dotted key such as `user.name` reaches a nested field.'),
                'application' => self::applicationProperty(),
            ],
            required: ['route'],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->buildPayloadAction->execute(
            route: (string) $arguments['route'],
            mode: (string) ($arguments['mode'] ?? 'valid'),
            overrides: (array) ($arguments['overrides'] ?? []),
            application: self::readOptionalString(arguments: $arguments, key: 'application'),
        );
    }
}
