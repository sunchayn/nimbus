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
