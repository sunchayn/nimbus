<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListRoutesAction;

class ListRoutesTool extends NimbusTool
{
    public function __construct(
        private readonly ListRoutesAction $listRoutesAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_list_routes',
            description: 'Lists the routes Nimbus extracted from the application. Each route has an id such as `POST api/users` to use in the other tools.',
            properties: [
                'search' => self::stringProperty('Text to look for in the route id, URI, or name.'),
                'method' => self::stringProperty('Only routes using this HTTP method.'),
                'prefix' => self::stringProperty('Only routes whose URI starts with this path.'),
                'limit' => self::integerProperty('Most routes to return. The default is 50 and the maximum is 200.'),
                'application' => self::applicationProperty(),
            ],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->listRoutesAction->execute(
            search: $arguments['search'] ?? null,
            method: $arguments['method'] ?? null,
            prefix: $arguments['prefix'] ?? null,
            limit: (int) ($arguments['limit'] ?? ListRoutesAction::DEFAULT_LIMIT),
            application: self::readOptionalString(arguments: $arguments, key: 'application'),
        );
    }
}
