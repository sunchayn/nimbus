<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ExplainResponseAction;

class ExplainResponseTool extends NimbusTool
{
    public function __construct(
        private readonly ExplainResponseAction $explainResponseAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_explain_response',
            description: 'Explains the likely cause of a response sent with nimbus_send_request, from its status, validation errors, authorization checks, exception, and queries.',
            properties: [
                'sent_request_id' => self::stringProperty('The `sentRequestId` returned by nimbus_send_request.'),
            ],
            required: ['sent_request_id'],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->explainResponseAction->execute(
            sentRequestId: (string) $arguments['sent_request_id'],
        );
    }
}
