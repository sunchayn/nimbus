<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListApplicationsAction;

class ListApplicationsTool extends NimbusTool
{
    public function __construct(
        private readonly ListApplicationsAction $listApplicationsAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_list_applications',
            description: 'Lists the applications Nimbus knows, with their routes prefix, auth guard, route extraction strategy, and global headers. Headers under `generated` map a name to a generator alias. Use a key as `application` in the route tools.',
            properties: [],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->listApplicationsAction->execute();
    }
}
