<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Contracts;

use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionResultData;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;

/**
 * Sends one request to the application on behalf of an agent.
 */
interface RequestExecutor
{
    /**
     * @throws McpToolException
     */
    public function execute(ExecutionRequestData $executionRequestData): ExecutionResultData;
}
