<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\Response;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\ReorderWorkspaceFoldersRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReorderWorkspaceFoldersAction;

class ReorderWorkspaceFoldersController
{
    public function __construct(
        private readonly ReorderWorkspaceFoldersAction $reorderWorkspaceFoldersAction,
    ) {}

    public function __invoke(ReorderWorkspaceFoldersRequest $request): Response
    {
        $this->reorderWorkspaceFoldersAction->execute(
            folders: $request->folderPaths('folders'),
        );

        return response()->noContent();
    }
}
