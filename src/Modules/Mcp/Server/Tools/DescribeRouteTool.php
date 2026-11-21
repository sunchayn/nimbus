<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DescribeRouteAction;

class DescribeRouteTool extends NimbusTool
{
    public function __construct(
        private readonly DescribeRouteAction $describeRouteAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_describe_route',
            description: 'Returns the request body schema of a route, its required fields, and any extraction error.',
            properties: [
                'route' => self::stringProperty('A route id from nimbus_list_routes, or a route name.'),
                'application' => self::applicationProperty(),
            ],
            required: ['route'],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->describeRouteAction->execute(
            route: (string) $arguments['route'],
            application: self::readOptionalString(arguments: $arguments, key: 'application'),
        );
    }
}
