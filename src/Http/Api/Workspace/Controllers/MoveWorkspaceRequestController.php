<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\MoveWorkspaceRequestRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\MoveWorkspaceRequestAction;

class MoveWorkspaceRequestController
{
    public function __construct(
        private readonly MoveWorkspaceRequestAction $moveWorkspaceRequestAction,
    ) {}

    public function __invoke(MoveWorkspaceRequestRequest $request): JsonResponse
    {
        $savedRequest = $this->moveWorkspaceRequestAction->execute(
            data: $request->toData(),
        );

        return response()->json(
            $savedRequest->toArray(),
        );
    }
}
