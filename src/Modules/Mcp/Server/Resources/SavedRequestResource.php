<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Resources;

use Mcp\Schema\ResourceTemplate;
use Mcp\Server\ClientGateway;
use Mcp\Server\Exception\ResourceReadException;
use Mcp\Server\Handler\ResourceTemplateHandlerInterface;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

class SavedRequestResource implements ResourceTemplateHandlerInterface
{
    public function __construct(
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
    ) {}

    public function definition(): ResourceTemplate
    {
        return new ResourceTemplate(
            uriTemplate: 'nimbus://workspace/requests/{id}',
            name: 'saved_request',
            description: 'A request file saved in the Nimbus workspace. Use the request id from nimbus_list_saved_requests.',
            mimeType: 'application/json',
        );
    }

    /**
     * @param  array<string, string>  $variables
     *
     * @throws ResourceReadException
     */
    public function read(string $uri, array $variables, ClientGateway $gateway): string
    {
        $id = rawurldecode($variables['id'] ?? '');

        try {
            $workspace = $this->scanWorkspaceAction->execute();

            $requestFile = $workspace->entries->findRequestOrFail($id);
        } catch (WorkspaceException $workspaceException) {
            throw new ResourceReadException($workspaceException->getMessage(), 0, $workspaceException);
        }

        return json_encode(
            value: $requestFile->savedRequest->toArray(),
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }
}
