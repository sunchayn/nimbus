<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\RoutesProcessors\Strategies;

use cebe\openapi\spec\OpenApi;
use Mockery;
use Mockery\LegacyMockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractOpenApiRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\OpenApiParsingException;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\RouteReconciliationService;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\AutoDetectRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\OpenAPISchemaRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

#[CoversClass(OpenAPISchemaRoutesProcessor::class)]
#[CoversClass(OpenApiParsingException::class)]
class OpenAPISchemaRoutesProcessorUnitTest extends TestCase
{
    public static bool $simulateMissingOpenApiPackage = false;

    private OpenAPISchemaRoutesProcessor $openAPISchemaRoutesProcessor;

    private ActiveApplicationResolver&LegacyMockInterface $activeApplicationResolverMock;

    private ExtractOpenApiRoutesAction&LegacyMockInterface $extractOpenApiRoutesActionMock;

    private AutoDetectRoutesProcessor&LegacyMockInterface $autoDetectRoutesProcessorMock;

    private RouteReconciliationService&LegacyMockInterface $reconciliationServiceMock;

    protected function tearDown(): void
    {
        self::$simulateMissingOpenApiPackage = false;
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeApplicationResolverMock = Mockery::mock(ActiveApplicationResolver::class);
        $this->extractOpenApiRoutesActionMock = Mockery::mock(ExtractOpenApiRoutesAction::class);
        $this->autoDetectRoutesProcessorMock = Mockery::mock(AutoDetectRoutesProcessor::class);
        $this->reconciliationServiceMock = Mockery::mock(RouteReconciliationService::class);

        $this->openAPISchemaRoutesProcessor = new OpenAPISchemaRoutesProcessor(
            activeApplicationResolver: $this->activeApplicationResolverMock,
            extractOpenApiRoutesAction: $this->extractOpenApiRoutesActionMock,
            autoDetectRoutesProcessor: $this->autoDetectRoutesProcessorMock,
            routeReconciliationService: $this->reconciliationServiceMock,
        );
    }

    public function test_it_returns_correct_strategy_name(): void
    {
        $this->assertEquals(RoutesProcessingStrategyEnum::OpenAPI, $this->openAPISchemaRoutesProcessor->getName());
    }

    #[DataProvider('supportedFileFormatsProvider')]
    public function test_it_processes_supported_file_formats(string $filename): void
    {
        // Arrange

        $openApiFile = __DIR__ . '/Stubs/OpenApi/'.$filename;

        $extractedOpenApiRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/v1/users', 'api', true),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        $autoDetectedRoutes = ExtractedRoutesCollection::make(['foo']);
        $finalCollection = ExtractedRoutesCollection::make(['foobar']);

        // Anticipate

        $this->activeApplicationResolverMock
            ->shouldReceive('isVersioned')
            ->andReturn(true);

        $this->activeApplicationResolverMock
            ->shouldReceive('getOpenApiFiles')
            ->once()
            ->andReturn(['v1' => $openApiFile]);

        $this->extractOpenApiRoutesActionMock
            ->shouldReceive('execute')
            ->once()
            ->with(Mockery::type(OpenApi::class), 'v1')
            ->andReturn([$extractedOpenApiRoute]);

        $this->autoDetectRoutesProcessorMock
            ->shouldReceive('process')
            ->once()
            ->andReturn($autoDetectedRoutes);

        $this->reconciliationServiceMock
            ->shouldReceive('execute')
            ->once()
            ->with(
                Mockery::on(fn ($collection) => $collection->count() === 1 && $collection->first() === $extractedOpenApiRoute),
                $autoDetectedRoutes
            )
            ->andReturn($finalCollection);

        // Act

        $result = $this->openAPISchemaRoutesProcessor->process();

        // Assert

        $this->assertSame($finalCollection, $result);
    }

    public static function supportedFileFormatsProvider(): \Generator
    {
        yield 'YAML file' => [
            'filename' => 'valid_openapi.yaml',
        ];

        yield 'YML file' => [
            'filename' => 'valid_openapi.yml',
        ];

        yield 'JSON file' => [
            'filename' => 'valid_openapi.json',
        ];
    }

    public function test_it_throws_exception_when_file_does_not_exist(): void
    {
        // Arrange

        $missingFile = __DIR__ . '/Stubs/OpenApi/missing.yaml';

        // Anticipate

        $this->activeApplicationResolverMock
            ->shouldReceive('getOpenApiFiles')
            ->once()
            ->andReturn(['v1' => $missingFile]);

        $this->activeApplicationResolverMock
            ->shouldReceive('isVersioned')
            ->andReturn(true);

        // Act & Assert

        try {
            $this->openAPISchemaRoutesProcessor->process();

            $this->fail('Exception was not thrown.');
        } catch (OpenApiParsingException $e) {
            $this->assertEquals('Failed to parse OpenAPI specification file: '.$missingFile, $e->getMessage());
            $this->assertStringContainsString('File does not exist.', $e->toArray()['suggestedSolution']);
        }
    }

    public function test_it_throws_exception_for_unsupported_extensions(): void
    {
        // Arrange

        $invalidFile = __DIR__ . '/Stubs/OpenApi/invalid_extension.txt';

        $this->activeApplicationResolverMock
            ->shouldReceive('getOpenApiFiles')
            ->once()
            ->andReturn(['v1' => $invalidFile]);

        $this->activeApplicationResolverMock
            ->shouldReceive('isVersioned')
            ->andReturn(true);

        // Act & Assert

        try {
            $this->openAPISchemaRoutesProcessor->process();

            $this->fail('Exception was not thrown.');
        } catch (OpenApiParsingException $e) {
            $this->assertEquals('Failed to parse OpenAPI specification file: '.$invalidFile, $e->getMessage());
            $this->assertStringContainsString('Unsupported file extension: txt. Use .json, .yaml, or .yml.', $e->toArray()['suggestedSolution']);
        }
    }

    public function test_it_uses_default_version_when_app_is_not_versioned(): void
    {
        // Arrange

        $openApiFile = __DIR__ . '/Stubs/OpenApi/valid_openapi.yaml';

        $extractedOpenApiRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/v1/users', 'api', true),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        $finalCollection = ExtractedRoutesCollection::make(['foobar']);

        // Anticipate

        $this->activeApplicationResolverMock
            ->shouldReceive('isVersioned')
            ->andReturn(false);

        $this->activeApplicationResolverMock
            ->shouldReceive('getOpenApiFiles')
            ->once()
            ->andReturn(['v1' => $openApiFile]);

        $this->extractOpenApiRoutesActionMock
            ->shouldReceive('execute')
            ->once()
            ->with(Mockery::type(OpenApi::class), 'default')
            ->andReturn([$extractedOpenApiRoute]);

        $this->autoDetectRoutesProcessorMock
            ->shouldReceive('process')
            ->andReturn(ExtractedRoutesCollection::make([]));

        $this->reconciliationServiceMock
            ->shouldReceive('execute')
            ->andReturn($finalCollection);

        // Act

        $result = $this->openAPISchemaRoutesProcessor->process();

        // Assert

        $this->assertSame($finalCollection, $result);
    }

    public function test_it_throws_exception_when_openapi_package_missing(): void
    {
        // Arrange

        self::$simulateMissingOpenApiPackage = true;

        $this->expectException(\Sunchayn\Nimbus\Modules\Routes\Exceptions\OpenApiPackageNotInstalledException::class);

        // Act

        new OpenAPISchemaRoutesProcessor(
            activeApplicationResolver: $this->activeApplicationResolverMock,
            extractOpenApiRoutesAction: $this->extractOpenApiRoutesActionMock,
            autoDetectRoutesProcessor: $this->autoDetectRoutesProcessorMock,
            routeReconciliationService: $this->reconciliationServiceMock,
        );
    }

    public function test_it_wraps_generic_exceptions_during_parsing(): void
    {
        // Arrange

        $brokenFile = __DIR__ . '/Stubs/OpenApi/broken.json';

        $this->activeApplicationResolverMock
            ->shouldReceive('getOpenApiFiles')
            ->andReturn(['v1' => $brokenFile]);

        $this->activeApplicationResolverMock
            ->shouldReceive('isVersioned')
            ->andReturn(true);

        // Act & Assert

        try {
            $this->openAPISchemaRoutesProcessor->process();
            $this->fail('Exception not thrown');
        } catch (OpenApiParsingException $e) {
            $this->assertStringContainsString('Failed to parse', $e->getMessage());
            $this->assertInstanceOf(\Throwable::class, $e->getPrevious());
        }
    }

    public function test_openapi_routes_include_only_operation_id_in_keywords(): void
    {
        // Arrange

        $openApiFile = __DIR__ . '/Stubs/OpenApi/valid_openapi.yaml';

        $autoDetectedRoutes = ExtractedRoutesCollection::make([]);

        // Anticipate

        $this->activeApplicationResolverMock
            ->shouldReceive('isVersioned')
            ->andReturn(true);

        $this->activeApplicationResolverMock
            ->shouldReceive('getRoutesPrefix')
            ->andReturn('api');

        $this->activeApplicationResolverMock
            ->shouldReceive('getOpenApiFiles')
            ->once()
            ->andReturn(['v1' => $openApiFile]);

        // Let the real ExtractOpenApiRoutesAction run
        $realExtractAction = new ExtractOpenApiRoutesAction($this->activeApplicationResolverMock);

        $this->extractOpenApiRoutesActionMock
            ->shouldReceive('execute')
            ->once()
            ->andReturnUsing(fn ($openapi, $version) => $realExtractAction->execute($openapi, $version));

        $this->autoDetectRoutesProcessorMock
            ->shouldReceive('process')
            ->once()
            ->andReturn($autoDetectedRoutes);

        // Capture the reconciliation call to verify keywords
        $capturedOpenApiRoutes = null;
        $this->reconciliationServiceMock
            ->shouldReceive('execute')
            ->once()
            ->andReturnUsing(function ($openApiRoutes, $appRoutes) use (&$capturedOpenApiRoutes) {
                $capturedOpenApiRoutes = $openApiRoutes;

                return $openApiRoutes; // Just return as-is for this test
            });

        // Act

        $this->openAPISchemaRoutesProcessor->process();

        // Assert

        $this->assertNotNull($capturedOpenApiRoutes);
        $this->assertGreaterThan(0, $capturedOpenApiRoutes->count());

        // Check that OpenAPI routes only have operation ID in keywords (not endpoint/shortUri)
        $routeWithOperationId = $capturedOpenApiRoutes->first(
            fn (ExtractedRoute $r) => isset($r->metadata['operationId'])
        );

        if ($routeWithOperationId) {
            // Should only contain operation ID, not endpoint or short URI
            $this->assertCount(1, $routeWithOperationId->keywords);
            $this->assertEquals(
                [$routeWithOperationId->metadata['operationId']],
                $routeWithOperationId->keywords
            );
        }
    }
}

namespace Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies;

use Sunchayn\Nimbus\Tests\App\Modules\Routes\RoutesProcessors\Strategies\OpenAPISchemaRoutesProcessorUnitTest;

function class_exists(string $class, bool $autoload = true): bool
{
    if ($class === \cebe\openapi\Reader::class && OpenAPISchemaRoutesProcessorUnitTest::$simulateMissingOpenApiPackage) {
        return false;
    }

    return \class_exists($class, $autoload);
}
