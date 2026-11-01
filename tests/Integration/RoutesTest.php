<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Http\Api\Routes\RoutesController;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractApplicationRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RoutesController::class)]
class RoutesTest extends TestCase
{
    public function test_it_returns_the_extracted_routes(): void
    {
        // Arrange

        $extractRoutesActionMock = $this->mock(ExtractApplicationRoutesAction::class);

        $extractedRoutesCollectionStub = new class extends ExtractedRoutesCollection
        {
            public function __construct() {}

            public function toFrontendArray(bool $shouldNestFolders = false): array
            {
                return ['v1' => [['name' => 'users']]];
            }
        };

        // Anticipate

        $extractRoutesActionMock->shouldReceive('execute')->andReturn($extractedRoutesCollectionStub);

        // Act

        $response = $this->getJson(route('nimbus.api.routes'));

        // Assert

        $response
            ->assertOk()
            ->assertExactJson(['v1' => [['name' => 'users']]]);
    }

    #[DataProvider('snapshotHeaderProvider')]
    public function test_it_reports_the_snapshot_time_only_when_the_snapshot_is_enabled(bool $enabled, ?string $expectedHeader): void
    {
        // Arrange

        config(['nimbus.snapshot' => $enabled]);

        $extractApplicationRoutesActionMock = $this->mock(ExtractApplicationRoutesAction::class);

        $manifestManagerSpy = $this->spy(ManifestManager::class);
        $manifestManagerSpy->shouldReceive('isStale')->andReturn(true);
        $manifestManagerSpy->shouldReceive('createdAt')->andReturn(1700000000);

        // Anticipate

        $extractApplicationRoutesActionMock->shouldReceive('execute')->andReturn(new ExtractedRoutesCollection);

        // Act

        $response = $this->getJson(route('nimbus.api.routes'));

        // Assert

        $expectedHeader === null
            ? $response->assertHeaderMissing('X-Nimbus-Snapshot-At')
            : $response->assertHeader('X-Nimbus-Snapshot-At', $expectedHeader);
    }

    public static function snapshotHeaderProvider(): Generator
    {
        yield 'snapshot enabled' => [
            'enabled' => true,
            'expectedHeader' => '1700000000',
        ];

        yield 'snapshot disabled' => [
            'enabled' => false,
            'expectedHeader' => null,
        ];
    }

    #[DataProvider('refreshProvider')]
    public function test_it_clears_the_snapshot_only_on_a_refresh_when_enabled(bool $enabled, string $query, bool $expectedCleared): void
    {
        // Arrange

        config(['nimbus.snapshot' => $enabled]);

        $extractApplicationRoutesActionMock = $this->mock(ExtractApplicationRoutesAction::class);

        $manifestManagerSpy = $this->spy(ManifestManager::class);
        $manifestManagerSpy->shouldReceive('isStale')->andReturn(true);

        // Anticipate

        $extractApplicationRoutesActionMock->shouldReceive('execute')->andReturn(new ExtractedRoutesCollection);

        // Act

        $this->getJson(route('nimbus.api.routes').$query)->assertOk();

        // Assert

        $expectedCleared
            ? $manifestManagerSpy->shouldHaveReceived('clear')->once()
            : $manifestManagerSpy->shouldNotHaveReceived('clear');
    }

    public static function refreshProvider(): Generator
    {
        yield 'refresh when enabled' => [
            'enabled' => true,
            'query' => '?refresh=1',
            'expectedCleared' => true,
        ];

        yield 'no refresh when enabled' => [
            'enabled' => true,
            'query' => '',
            'expectedCleared' => false,
        ];

        yield 'refresh when disabled' => [
            'enabled' => false,
            'query' => '?refresh=1',
            'expectedCleared' => false,
        ];
    }

    #[DataProvider('nestedFoldersProvider')]
    public function test_it_passes_the_nested_folders_setting_to_the_routes(?bool $configured, bool $expected): void
    {
        // Arrange

        if ($configured !== null) {
            config(['nimbus.nested_folders' => $configured]);
        }

        $extractRoutesActionMock = $this->mock(ExtractApplicationRoutesAction::class);

        $extractedRoutesCollectionStub = new class extends ExtractedRoutesCollection
        {
            public function __construct() {}

            public function toFrontendArray(bool $shouldNestFolders = false): array
            {
                return ['nestedFolders' => $shouldNestFolders];
            }
        };

        // Anticipate

        $extractRoutesActionMock->shouldReceive('execute')->andReturn($extractedRoutesCollectionStub);

        // Act

        $response = $this->getJson(route('nimbus.api.routes'));

        // Assert

        $response->assertOk()->assertExactJson(['nestedFolders' => $expected]);
    }

    public static function nestedFoldersProvider(): Generator
    {
        yield 'enabled' => ['configured' => true, 'expected' => true];

        yield 'disabled' => ['configured' => false, 'expected' => false];

        yield 'not configured defaults to disabled' => ['configured' => null, 'expected' => false];
    }

    public function test_it_returns_extraction_errors(): void
    {
        // Arrange

        $extractRoutesActionMock = $this->mock(ExtractApplicationRoutesAction::class);

        $exception = new class(message: fake()->words(asText: true), routeUri: fake()->url(), routeMethods: fake()->words(2), controllerClass: fake()->word(), controllerMethod: fake()->word(), suggestedSolution: fake()->words(asText: true)) extends RouteExtractionException {};

        // Anticipate

        $extractRoutesActionMock->shouldReceive('execute')->andThrow($exception);

        // Act

        $response = $this->getJson(route('nimbus.api.routes'));

        // Assert

        $response
            ->assertStatus(500)
            ->assertExactJson([
                'routeExtractorException' => [
                    'exception' => [
                        'message' => $exception->getMessage(),
                        'previous' => null,
                    ],
                    'routeContext' => $exception->getRouteContext(),
                    'suggestedSolution' => $exception->getSuggestedSolution(),
                    'ignoreData' => $exception->getIgnoreData(),
                ],
            ]);
    }

    public function test_it_integrates(): void
    {
        // Act

        $response = $this->getJson(route('nimbus.api.routes'));

        // Assert

        $response->assertOk();
    }
}
