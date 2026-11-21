<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\SaveRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\SaveRequestData;

class SaveRequestTool extends NimbusTool
{
    public function __construct(
        private readonly SaveRequestAction $saveRequestAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_save_request',
            description: 'Saves a new request file in the Nimbus workspace. It cannot save credentials, so use {{variables}} for secrets. It never replaces a file.',
            properties: [
                'name' => self::stringProperty('The request name. It also gives the file name.'),
                'method' => self::stringProperty('The HTTP method.'),
                'endpoint' => self::stringProperty('The path, such as `/api/users`.'),
                'route' => self::stringProperty('A route id from nimbus_list_routes.'),
                'headers' => self::objectProperty('Request headers. Credential headers are refused.'),
                'query' => self::objectProperty('Query string values.'),
                'body' => self::bodyProperty(),
                'folder' => self::stringProperty('A folder under requests/, such as `users`.'),
            ],
            required: ['name', 'method', 'endpoint'],
        );
    }

    protected function handle(array $arguments): array
    {
        $data = new SaveRequestData(
            name: (string) ($arguments['name'] ?? ''),
            method: (string) ($arguments['method'] ?? ''),
            endpoint: (string) ($arguments['endpoint'] ?? ''),
            route: self::readOptionalString(arguments: $arguments, key: 'route'),
            headers: (array) ($arguments['headers'] ?? []),
            query: (array) ($arguments['query'] ?? []),
            body: self::readBody(arguments: $arguments),
            folder: self::readOptionalString(arguments: $arguments, key: 'folder'),
        );

        return $this->saveRequestAction->execute(data: $data);
    }
}
