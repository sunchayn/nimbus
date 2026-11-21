<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ValidatePayloadTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ValidatePayloadTool::class)]
class ValidatePayloadToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_read_only_tool_that_requires_a_route_and_a_body(): void
    {
        // Arrange

        $tool = resolve(ValidatePayloadTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_validate_payload',
            $definition->name,
        );

        $this->assertSame(
            [
                'route',
                'body',
            ],
            $definition->inputSchema['required'],
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }
}
