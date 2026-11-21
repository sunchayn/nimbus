<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DecryptValueAction;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\DecryptValueTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DecryptValueTool::class)]
class DecryptValueToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_read_only_tool_that_requires_a_value(): void
    {
        // Arrange

        $tool = resolve(DecryptValueTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_decrypt_value',
            $definition->name,
        );

        $this->assertSame(
            ['value'],
            $definition->inputSchema['required'],
        );

        $this->assertSame(
            DecryptValueAction::KINDS,
            $definition->inputSchema['properties']['kind']['enum'],
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }
}
