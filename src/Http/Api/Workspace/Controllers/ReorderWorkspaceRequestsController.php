<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\Response;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\ReorderWorkspaceRequestsRequest;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReorderWorkspaceRequestsAction;

class ReorderWorkspaceRequestsController
{
    public function __construct(
        private readonly ReorderWorkspaceRequestsAction $reorderWorkspaceRequestsAction,
    ) {}

    public function __invoke(ReorderWorkspaceRequestsRequest $request): Response
    {
        $this->reorderWorkspaceRequestsAction->execute(
            data: $request->toData(),
        );

        return response()->noContent();
    }
}
