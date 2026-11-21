<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Server\Tools;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DecryptValueAction;

class DecryptValueTool extends NimbusTool
{
    public function __construct(
        private readonly DecryptValueAction $decryptValueAction,
    ) {}

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'nimbus_decrypt_value',
            description: 'Decrypts a value encrypted by this application, such as a cookie from a response, with the application key.',
            properties: [
                'value' => self::stringProperty('The encrypted value.'),
                'kind' => self::stringProperty(
                    description: '`cookie` for an encrypted cookie, `payload` for a value made by `encrypt()`.',
                    enum: DecryptValueAction::KINDS,
                ),
                'name' => self::stringProperty('The cookie name. It lets Nimbus remove the cookie prefix from the result.'),
            ],
            required: ['value'],
            annotations: self::readOnly(),
        );
    }

    protected function handle(array $arguments): array
    {
        return $this->decryptValueAction->execute(
            value: (string) $arguments['value'],
            kind: (string) ($arguments['kind'] ?? 'cookie'),
            name: self::readOptionalString(arguments: $arguments, key: 'name'),
        );
    }
}
