<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\RenameWorkspaceFolderRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\RenameWorkspaceFolderAction;

class RenameWorkspaceFolderController
{
    public function __construct(
        private readonly RenameWorkspaceFolderAction $renameWorkspaceFolderAction,
    ) {}

    public function __invoke(RenameWorkspaceFolderRequest $request): JsonResponse
    {
        $folder = $this->renameWorkspaceFolderAction->execute(
            folder: $request->folderPath(),
            newName: $request->folderPath('name'),
        );

        return response()->json(
            data: ['folder' => (string) $folder],
        );
    }
}
