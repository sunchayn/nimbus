<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Tools\ExplainResponseTool;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExplainResponseTool::class)]
class ExplainResponseToolFunctionalTest extends TestCase
{
    public function test_it_defines_a_read_only_tool_that_requires_a_sent_request_id(): void
    {
        // Arrange

        $tool = resolve(ExplainResponseTool::class);

        // Act

        $definition = $tool->definition();

        // Assert

        $this->assertSame(
            'nimbus_explain_response',
            $definition->name,
        );

        $this->assertSame(
            ['sent_request_id'],
            $definition->inputSchema['required'],
        );

        $this->assertTrue($definition->annotations->readOnlyHint);
    }
}
