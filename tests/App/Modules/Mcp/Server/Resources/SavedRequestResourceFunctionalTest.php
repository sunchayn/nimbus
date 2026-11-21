<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Server\Resources;

use Mcp\Server\ClientGateway;
use Mcp\Server\Exception\ResourceReadException;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Server\Resources\SavedRequestResource;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SavedRequestResource::class)]
class SavedRequestResourceFunctionalTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_defines_a_template_for_saved_requests(): void
    {
        // Arrange

        $resource = resolve(SavedRequestResource::class);

        // Act

        $definition = $resource->definition();

        // Assert

        $this->assertSame(
            'nimbus://workspace/requests/{id}',
            $definition->uriTemplate,
        );

        $this->assertSame(
            'application/json',
            $definition->mimeType,
        );
    }

    public function test_it_reads_a_saved_request_as_json(): void
    {
        // Arrange

        $this->enableWorkspace();

        $this->saveRequest(
            savedRequest: $this->savedRequest(id: 'request-1'),
            hash: null,
        );

        $resource = resolve(SavedRequestResource::class);

        // Act

        $content = $resource->read(
            uri: 'nimbus://workspace/requests/request-1',
            variables: [
                'id' => 'request-1',
            ],
            gateway: $this->createStub(ClientGateway::class),
        );

        // Assert

        $decoded = json_decode($content, true);

        $this->assertSame(
            'request-1',
            $decoded['id'],
        );

        $this->assertSame(
            '{{base_url}}/api/users',
            $decoded['endpoint'],
        );
    }

    public function test_it_decodes_the_id_of_the_uri(): void
    {
        // Arrange

        $this->enableWorkspace();

        $this->saveRequest(
            savedRequest: $this->savedRequest(id: 'request 1'),
            hash: null,
        );

        $resource = resolve(SavedRequestResource::class);

        // Act

        $content = $resource->read(
            uri: 'nimbus://workspace/requests/request%201',
            variables: [
                'id' => 'request%201',
            ],
            gateway: $this->createStub(ClientGateway::class),
        );

        // Assert

        $this->assertSame(
            'request 1',
            json_decode($content, true)['id'],
        );
    }

    public function test_it_fails_for_an_unknown_request(): void
    {
        // Arrange

        $this->enableWorkspace();

        $resource = resolve(SavedRequestResource::class);

        $this->expectException(ResourceReadException::class);
        $this->expectExceptionMessage('does not exist in the workspace');

        // Act

        $resource->read(
            uri: 'nimbus://workspace/requests/unknown',
            variables: [
                'id' => 'unknown',
            ],
            gateway: $this->createStub(ClientGateway::class),
        );
    }

    public function test_it_fails_when_the_workspace_is_disabled(): void
    {
        // Arrange

        config()->set('nimbus.workspace.enabled', false);

        $resource = resolve(SavedRequestResource::class);

        $this->expectException(ResourceReadException::class);

        // Act

        $resource->read(
            uri: 'nimbus://workspace/requests/request-1',
            variables: [
                'id' => 'request-1',
            ],
            gateway: $this->createStub(ClientGateway::class),
        );
    }
}
