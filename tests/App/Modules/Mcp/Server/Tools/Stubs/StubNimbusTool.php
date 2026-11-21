<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools\Stubs;

use Mcp\Schema\Tool;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\NimbusTool;
use Throwable;

class StubNimbusTool extends NimbusTool
{
    public ?Throwable $failure = null;

    public function definition(): Tool
    {
        return self::defineTool(
            name: 'stub_tool',
            description: 'A stub tool.',
            properties: [
                'name' => self::stringProperty('A name.'),
            ],
            required: ['name'],
            annotations: self::readOnly(),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function listProperties(): array
    {
        return [
            'plain' => self::stringProperty('A plain string.'),
            'enum' => self::stringProperty(
                description: 'A string from a list.',
                enum: ['one', 'two'],
            ),
            'application' => self::applicationProperty(),
            'object' => self::objectProperty('An object.'),
            'integer' => self::integerProperty('An integer.'),
            'body' => self::bodyProperty(),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function readString(array $arguments, string $key): ?string
    {
        return self::readOptionalString(arguments: $arguments, key: $key);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<array-key, mixed>|string
     */
    public function readBodyOf(array $arguments): array|string
    {
        return self::readBody(arguments: $arguments);
    }

    protected function handle(array $arguments): array
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $arguments;
    }
}
