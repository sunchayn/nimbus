<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\CreateWorkspaceFolderRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\CreateWorkspaceFolderAction;

class CreateWorkspaceFolderController
{
    public function __construct(
        private readonly CreateWorkspaceFolderAction $createWorkspaceFolderAction,
    ) {}

    public function __invoke(CreateWorkspaceFolderRequest $request): JsonResponse
    {
        $folder = $this->createWorkspaceFolderAction->execute(
            folder: $request->folderPath(),
        );

        return response()->json(
            data: ['folder' => (string) $folder],
            status: 201,
        );
    }
}
