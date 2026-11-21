<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Mcp;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Resources\SavedRequestResource;
use Sunchayn\Nimbus\Tests\Concerns\InteractsWithMcpServer;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SavedRequestResource::class)]
class NimbusMcpSavedRequestResourceTest extends TestCase
{
    use InteractsWithMcpServer;
    use UsesTemporaryWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableWorkspace();
    }

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_reports_an_unknown_resource_as_an_error(): void
    {
        // Act

        $response = $this->readResource(uri: 'nimbus://workspace/requests/unknown');

        // Assert

        $response
            ->assertHasErrors()
            ->assertSee('does not exist in the workspace');
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $this->saveRequest(
            savedRequest: $this->savedRequest(id: 'request-1'),
            hash: null,
        );

        // Act

        $response = $this->readResource(uri: 'nimbus://workspace/requests/request-1');

        // Assert

        $response
            ->assertOk()
            ->assertSee('{{base_url}}');
    }
}
