<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Controllers;

use Sunchayn\Nimbus\Http\Api\Workspace\Resources\WorkspaceResource;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;

class ListWorkspaceController
{
    public function __construct(
        private readonly ScanWorkspaceAction $scanWorkspaceAction,
    ) {}

    public function __invoke(): WorkspaceResource
    {
        $workspace = $this->scanWorkspaceAction->execute();

        return WorkspaceResource::make($workspace);
    }
}
