<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Illuminate\Http\Response;
use Sunchayn\Nimbus\Modules\Workspace\Actions\DeleteWorkspaceRequestAction;

class DeleteWorkspaceRequestController
{
    public function __construct(
        private readonly DeleteWorkspaceRequestAction $deleteWorkspaceRequestAction,
    ) {}

    public function __invoke(string $id): Response
    {
        $this->deleteWorkspaceRequestAction->execute($id);

        return response()->noContent();
    }
}
