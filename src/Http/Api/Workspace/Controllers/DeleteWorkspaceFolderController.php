<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\Response;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\DeleteWorkspaceFolderRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\DeleteWorkspaceFolderAction;

class DeleteWorkspaceFolderController
{
    public function __construct(
        private readonly DeleteWorkspaceFolderAction $deleteWorkspaceFolderAction,
    ) {}

    public function __invoke(DeleteWorkspaceFolderRequest $request): Response
    {
        $this->deleteWorkspaceFolderAction->execute(
            folder: $request->folderPath(),
        );

        return response()->noContent();
    }
}
