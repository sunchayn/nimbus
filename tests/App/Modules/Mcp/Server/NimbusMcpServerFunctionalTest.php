<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Mcp\Server\NimbusMcpServer;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(NimbusMcpServer::class)]
class NimbusMcpServerFunctionalTest extends TestCase
{
    use InteractsWithMcpServer;

    private const READ_TOOLS = [
        'nimbus_list_routes',
        'nimbus_describe_route',
        'nimbus_build_payload',
        'nimbus_validate_payload',
        'nimbus_decrypt_value',
        'nimbus_list_applications',
        'nimbus_list_environments',
        'nimbus_list_saved_requests',
    ];

    private const SEND_TOOLS = [
        'nimbus_send_request',
        'nimbus_explain_response',
        'nimbus_run_request',
    ];

    /**
     * @param  list<string>  $registered
     * @param  list<string>  $notRegistered
     */
    #[DataProvider('levels')]
    public function test_it_registers_the_tools_up_to_the_configured_level(
        string $level,
        array $registered,
        array $notRegistered,
    ): void {
        // Arrange

        config()->set('nimbus.mcp.level', $level);

        // Act

        $tools = $this->listTools();

        // Assert

        $tools->assertRegistered($registered);

        foreach ($notRegistered as $name) {
            $tools->assertNotRegistered($name);
        }
    }

    public static function levels(): Generator
    {
        yield 'read level' => [
            'level' => 'read',
            'registered' => self::READ_TOOLS,
            'notRegistered' => [...self::SEND_TOOLS, 'nimbus_save_request'],
        ];

        yield 'send level' => [
            'level' => 'send',
            'registered' => [...self::READ_TOOLS, ...self::SEND_TOOLS],
            'notRegistered' => ['nimbus_save_request'],
        ];

        yield 'write level' => [
            'level' => 'write',
            'registered' => [...self::READ_TOOLS, ...self::SEND_TOOLS, 'nimbus_save_request'],
            'notRegistered' => [],
        ];

        yield 'unknown level falls back to read' => [
            'level' => 'unknown',
            'registered' => self::READ_TOOLS,
            'notRegistered' => [...self::SEND_TOOLS, 'nimbus_save_request'],
        ];
    }

    public function test_it_registers_the_prompts(): void
    {
        // Act

        $prompts = $this->sendMcpRequest(method: 'prompts/list');

        // Assert

        $this->assertEqualsCanonicalizing(
            ['smoke_test_route', 'check_validation'],
            $prompts->names('prompts'),
        );
    }

    public function test_it_registers_the_saved_request_resource(): void
    {
        // Act

        $templates = $this->sendMcpRequest(method: 'resources/templates/list');

        // Assert

        $this->assertSame(
            ['saved_request'],
            $templates->names('resourceTemplates'),
        );
    }
}
