<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\BuildPayloadAction;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\BuildPayloadTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(BuildPayloadTool::class)]
class BuildPayloadToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_read_only_tool_that_requires_a_route(): void
    {
        // Arrange

        $tool = resolve(BuildPayloadTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_build_payload',
            $definition->name,
        );

        $this->assertSame(
            ['route'],
            $definition->inputSchema['required'],
        );

        $this->assertSame(
            BuildPayloadAction::MODES,
            $definition->inputSchema['properties']['mode']['enum'],
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }
}
