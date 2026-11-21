<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListEnvironmentsAction;

class ListEnvironmentsTool extends NimbusTool
{
    public function __construct(
        private readonly ListEnvironmentsAction $listEnvironmentsAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_list_environments',
            description: 'Lists the environments that requests can be sent to, with their base URLs.',
            properties: [],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->listEnvironmentsAction->execute();
    }
}
