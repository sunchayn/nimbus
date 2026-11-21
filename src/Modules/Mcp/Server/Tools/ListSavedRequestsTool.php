<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ListSavedRequestsAction;

class ListSavedRequestsTool extends NimbusTool
{
    public function __construct(
        private readonly ListSavedRequestsAction $listSavedRequestsAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_list_saved_requests',
            description: 'Lists the requests saved in the Nimbus workspace. It fails when the workspace is off.',
            properties: [
                'folder' => self::stringProperty('Only list the requests in this folder and its subfolders.'),
            ],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->listSavedRequestsAction->execute(
            folder: self::readOptionalString(arguments: $arguments, key: 'folder'),
        );
    }
}
