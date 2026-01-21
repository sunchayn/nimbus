<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Share\Services;

use Generator;
use Illuminate\Contracts\Config\Repository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Export\Services\ShareableLinkProcessorService;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ShareableLinkProcessorService::class)]
class ShareableLinkProcessorServiceUnitTest extends TestCase
{
    private Repository|MockInterface $configMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configMock = Mockery::mock(Repository::class);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/api/test', fn () => 'test');
        $router->post('/admin/users', fn () => 'users');
        $router->get('/admin/test', fn () => 'test');
    }

    public function test_it_decodes_valid_shareable_link_payload(): void
    {
        // Arrange

        $payload = $this->createMinimalPayload(method: 'GET', endpoint: '/api/users');
        $processor = $this->createProcessor($payload);

        // Act

        $decodedPayload = $processor->getDecodedPayload();

        // Assert

        $this->assertTrue($processor->hasPayload());
        $this->assertNotNull($decodedPayload);
        $this->assertSame('GET', $decodedPayload['method']);
        $this->assertSame('/api/users', $decodedPayload['endpoint']);
    }

    public function test_it_handles_invalid_base64_gracefully(): void
    {
        // Arrange

        $this->expectNoApplicationLookup();
        $processor = new ShareableLinkProcessorService($this->configMock);

        // Act

        $processor->process('!!!invalid-base64!!!');

        // Assert

        $this->assertFalse($processor->routeExists());
        $this->assertNotNull($processor->getError());
    }

    public function test_it_preserves_headers_and_query_parameters(): void
    {
        // Arrange

        $payload = $this->encodePayload([
            ...$this->basePayload(),
            'method' => 'POST',
            'endpoint' => '/api/orders',
            'headers' => [
                ['key' => 'Authorization', 'value' => 'Bearer token123'],
                ['key' => 'Content-Type', 'value' => 'application/json'],
            ],
            'queryParameters' => [
                ['key' => 'page', 'value' => '1'],
                ['key' => 'limit', 'value' => '25'],
            ],
            'payloadType' => 'json',
            'authorization' => ['type' => 'bearer', 'value' => 'token123'],
        ]);

        $processor = $this->createProcessor($payload);

        // Act

        $decodedPayload = $processor->getDecodedPayload();

        // Assert

        $this->assertNotNull($decodedPayload);
        $this->assertCount(2, $decodedPayload['headers']);
        $this->assertSame('Authorization', $decodedPayload['headers'][0]['key']);
        $this->assertCount(2, $decodedPayload['queryParameters']);
        $this->assertSame('page', $decodedPayload['queryParameters'][0]['key']);
    }

    public function test_it_sets_route_exists_to_false_when_route_not_found(): void
    {
        // Arrange

        $payload = $this->createMinimalPayload(method: 'DELETE', endpoint: '/api/non-existent-route');
        $processor = $this->createProcessor($payload);

        // Assert

        $this->assertFalse($processor->routeExists());
    }

    public function test_to_frontend_state_returns_complete_structure(): void
    {
        // Arrange

        $payload = $this->createMinimalPayload();
        $processor = $this->createProcessor($payload);

        // Act

        $frontendState = $processor->toFrontendState();

        // Assert

        $this->assertArrayHasKey('payload', $frontendState);
        $this->assertArrayHasKey('routeExists', $frontendState);
        $this->assertArrayHasKey('error', $frontendState);
    }

    #[DataProvider('providePayloadCompressionScenarios')]
    public function test_it_handles_different_payload_sizes(array $payloadData): void
    {
        // Arrange

        $payload = $this->encodePayload($payloadData);
        $processor = $this->createProcessor($payload);

        // Act

        $decodedPayload = $processor->getDecodedPayload();

        // Assert

        $this->assertTrue($processor->hasPayload());
        $this->assertSame($payloadData['method'], $decodedPayload['method']);
    }

    public static function providePayloadCompressionScenarios(): Generator
    {
        yield 'minimal payload' => [
            'payloadData' => [
                'method' => 'GET',
                'endpoint' => '/api',
                'headers' => [],
                'queryParameters' => [],
                'body' => [],
                'payloadType' => 'empty',
                'authorization' => ['type' => 'none'],
            ],
        ];

        yield 'payload with response' => [
            'payloadData' => [
                'method' => 'POST',
                'endpoint' => '/api/users',
                'headers' => [['key' => 'X-Test', 'value' => 'test']],
                'queryParameters' => [],
                'body' => [],
                'payloadType' => 'json',
                'authorization' => ['type' => 'none'],
                'response' => [
                    'status' => 200,
                    'statusCode' => 200,
                    'statusText' => 'OK',
                    'body' => '{"id": 1, "name": "Test User"}',
                    'sizeInBytes' => 30,
                    'headers' => [],
                    'cookies' => [],
                    'timestamp' => 1234567890,
                ],
            ],
        ];
    }

    public function test_it_handles_decompression_failure(): void
    {
        // Arrange

        $this->expectNoApplicationLookup();
        $processor = new ShareableLinkProcessorService($this->configMock);

        // Create invalid compressed data (valid base64 but invalid gzip)
        $invalidCompressed = base64_encode('not-gzip-data');

        // Act

        $processor->process($invalidCompressed);

        // Assert

        $this->assertFalse($processor->routeExists());
        $this->assertNotNull($processor->getError());
        $this->assertStringContainsString('Failed to decompress payload', $processor->getError());
    }

    public function test_it_handles_json_parsing_failure(): void
    {
        // Arrange

        $this->expectNoApplicationLookup();
        $processor = new ShareableLinkProcessorService($this->configMock);

        // Create valid gzip but invalid JSON
        $invalidJson = gzcompress('not valid json {]');
        $encoded = base64_encode($invalidJson);

        // Act

        $processor->process($encoded);

        // Assert

        $this->assertFalse($processor->routeExists());
        $this->assertNotNull($processor->getError());
        $this->assertStringContainsString('JSON', $processor->getError());
    }

    public function test_it_handles_url_safe_base64_with_padding(): void
    {
        // Arrange

        $payload = $this->basePayload();
        $payload['method'] = 'GET';
        $payload['endpoint'] = '/api/test';

        $json = json_encode($payload);
        $compressed = gzcompress($json);
        $base64 = base64_encode($compressed);

        // Convert to URL-safe and remove padding
        $urlSafe = rtrim(strtr($base64, '+/', '-_'), '=');

        $processor = $this->createProcessor($urlSafe);

        // Act

        $decodedPayload = $processor->getDecodedPayload();

        // Assert

        $this->assertNotNull($decodedPayload);
        $this->assertSame('GET', $decodedPayload['method']);
    }

    public function test_it_searches_other_applications_when_route_not_in_current_app(): void
    {
        // Arrange

        $payload = $this->encodePayload([
            ...$this->basePayload(),
            'method' => 'GET',
            'endpoint' => '/other-prefix/users',
        ]);

        // Mock config to return an application with a different prefix
        // The route won't exist in current app and won't be found in other apps either
        // because there are no actual routes defined
        $this->configMock
            ->shouldReceive('get')
            ->with('nimbus.applications', [])
            ->andReturn([
                'other-app' => [
                    'routes' => [
                        'prefix' => 'other-prefix',
                    ],
                ],
            ]);

        $processor = new ShareableLinkProcessorService($this->configMock);

        // Act

        $processor->process($payload);

        // Assert

        // Since no routes are defined anywhere, routeExists will be false
        $this->assertFalse($processor->routeExists());
        $this->assertNull($processor->getTargetApplication());
    }

    public function test_it_returns_null_for_target_application_when_route_in_current_app(): void
    {
        // Arrange

        $payload = $this->createMinimalPayload(method: 'GET', endpoint: '/api/test');
        $processor = $this->createProcessor($payload);

        // Assert

        $this->assertNull($processor->getTargetApplication());
    }

    public function test_it_handles_empty_json_decode(): void
    {
        // Arrange

        $this->expectNoApplicationLookup();
        $processor = new ShareableLinkProcessorService($this->configMock);

        // Create payload that decodes to null
        $nullJson = gzcompress('null');
        $encoded = base64_encode($nullJson);

        // Act

        $processor->process($encoded);

        // Assert

        $this->assertTrue($processor->hasPayload());
        $this->assertSame([], $processor->getDecodedPayload());
    }

    public function test_it_handles_application_config_without_routes_key(): void
    {
        // Arrange

        $payload = $this->encodePayload([
            ...$this->basePayload(),
            'method' => 'GET',
            'endpoint' => '/api/non-existent',
        ]);

        $this->configMock
            ->shouldReceive('get')
            ->with('nimbus.applications', [])
            ->andReturn([
                'app-without-routes' => [
                    'name' => 'Test App',
                ],
            ]);

        $processor = new ShareableLinkProcessorService($this->configMock);

        // Act

        $processor->process($payload);

        // Assert

        $this->assertFalse($processor->routeExists());
        $this->assertNull($processor->getTargetApplication());
    }

    public function test_frontend_state_includes_error_when_present(): void
    {
        // Arrange

        $this->expectNoApplicationLookup();
        $processor = new ShareableLinkProcessorService($this->configMock);

        // Act

        $processor->process('invalid-payload');
        $frontendState = $processor->toFrontendState();

        // Assert

        $this->assertArrayHasKey('error', $frontendState);
        $this->assertNotNull($frontendState['error']);
        $this->assertNull($frontendState['payload']);
        $this->assertFalse($frontendState['routeExists']);
    }

    /**
     * Creates a processor with the given encoded payload and processes it.
     */
    private function createProcessor(string $encodedPayload): ShareableLinkProcessorService
    {
        $this->expectNoApplicationLookup();

        $processor = new ShareableLinkProcessorService($this->configMock);
        $processor->process($encodedPayload);

        return $processor;
    }

    /**
     * Creates an encoded minimal payload with optional method and endpoint overrides.
     */
    private function createMinimalPayload(string $method = 'GET', string $endpoint = '/api/test'): string
    {
        return $this->encodePayload([
            ...$this->basePayload(),
            'method' => $method,
            'endpoint' => $endpoint,
        ]);
    }

    /**
     * Returns the base payload structure with default values.
     *
     * @return array<string, mixed>
     */
    private function basePayload(): array
    {
        return [
            'headers' => [],
            'queryParameters' => [],
            'body' => [],
            'payloadType' => 'empty',
            'authorization' => ['type' => 'none'],
        ];
    }

    private function expectNoApplicationLookup(): void
    {
        $this->configMock
            ->shouldReceive('get')
            ->with('nimbus.applications', [])
            ->andReturn([]);
    }

    /**
     * Encodes a payload using zlib compression matching frontend's pako.deflate().
     *
     * @param  array<string, mixed>  $data
     */
    private function encodePayload(array $data): string
    {
        $json = json_encode($data);
        $compressed = gzcompress($json);
        $base64 = base64_encode($compressed);

        return rtrim(strtr($base64, '+/', '-_'), '=');
    }

    public function test_it_returns_early_when_route_exists_in_current_app(): void
    {
        // Arrange

        $payload = $this->createMinimalPayload(method: 'GET', endpoint: '/api/test');
        $processor = $this->createProcessor($payload);

        // Assert

        $this->assertTrue($processor->routeExists());
        $this->assertNull($processor->getTargetApplication());
    }

    public function test_it_sets_target_application_when_route_found_in_other_app(): void
    {
        // Arrange

        $payload = $this->encodePayload([
            ...$this->basePayload(),
            'method' => 'POST',
            'endpoint' => '/admin/users',
        ]);

        $this->configMock->shouldReceive('get')
            ->with('nimbus.applications', [])
            ->andReturn([
                'admin' => [
                    'routes' => ['prefix' => '/admin'],
                ],
            ]);

        $processor = new ShareableLinkProcessorService($this->configMock);

        // Act

        $processor->process($payload);

        // Assert

        $this->assertTrue($processor->routeExists());
        $this->assertEquals('admin', $processor->getTargetApplication());
    }

    public function test_it_returns_false_when_prefix_does_not_match(): void
    {
        // Arrange

        $payload = $this->encodePayload([
            ...$this->basePayload(),
            'method' => 'GET',
            'endpoint' => '/wrong/prefix',
        ]);

        $this->configMock->shouldReceive('get')
            ->with('nimbus.applications', [])
            ->andReturn([
                'admin' => [
                    'routes' => ['prefix' => '/admin'],
                ],
            ]);

        $processor = new ShareableLinkProcessorService($this->configMock);

        // Act

        $processor->process($payload);

        // Assert

        $this->assertFalse($processor->routeExists());
        $this->assertNull($processor->getTargetApplication());
    }
}
