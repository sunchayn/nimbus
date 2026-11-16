<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\SaveWorkspaceRequestRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\SaveWorkspaceRequestAction;

class SaveWorkspaceRequestController
{
    public function __construct(
        private readonly SaveWorkspaceRequestAction $saveWorkspaceRequestAction,
    ) {}

    public function __invoke(SaveWorkspaceRequestRequest $request): JsonResponse
    {
        $savedRequest = $this->saveWorkspaceRequestAction->execute(
            savedRequest: $request->savedRequest(),
            hash: $request->hash(),
            folder: $request->folderPath(),
        );

        return response()->json(
            $savedRequest->toArray(),
        );
    }
}
