<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\JsonResponse;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;

class ShowWorkspaceRequestController
{
    public function __construct(
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $workspace = $this->scanWorkspaceAction->execute();

        $requestFile = $workspace->entries->findRequestOrFail($id);

        return response()->json(
            $requestFile->toArray(),
        );
    }
}
