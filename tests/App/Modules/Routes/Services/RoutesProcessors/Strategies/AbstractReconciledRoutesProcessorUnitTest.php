<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services\RoutesProcessors\Strategies;

use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\RouteReconciliationService;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\AbstractReconciledRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\AutoDetectRoutesProcessor;

#[CoversClass(AbstractReconciledRoutesProcessor::class)]
class AbstractReconciledRoutesProcessorUnitTest extends TestCase
{
    public function test_it_reconciles_external_routes_with_auto_detected_routes(): void
    {
        // Arrange

        $autoDetectMock = Mockery::mock(AutoDetectRoutesProcessor::class);
        $reconciliationServiceMock = Mockery::mock(RouteReconciliationService::class);

        $externalRoutes = ExtractedRoutesCollection::make(['foo']);
        $autoDetectedRoutes = ExtractedRoutesCollection::make(['bar']);
        $reconciledRoutes = ExtractedRoutesCollection::make(['foobar']);

        $autoDetectMock->shouldReceive('process')->once()->andReturn($autoDetectedRoutes);

        $reconciliationServiceMock->shouldReceive('execute')
            ->once()
            ->with($externalRoutes, $autoDetectedRoutes)
            ->andReturn($reconciledRoutes);

        $processor = new class($autoDetectMock, $reconciliationServiceMock, $externalRoutes) extends AbstractReconciledRoutesProcessor
        {
            public function __construct(
                AutoDetectRoutesProcessor $autoDetect,
                RouteReconciliationService $reconciliation,
                private ExtractedRoutesCollection $externalRoutes
            ) {
                parent::__construct($autoDetect, $reconciliation);
            }

            protected function getRoutesFromExternalSource(): ExtractedRoutesCollection
            {
                return $this->externalRoutes;
            }

            public function getName(): RoutesProcessingStrategyEnum
            {
                return RoutesProcessingStrategyEnum::AutoDetect;
            }
        };

        // Act

        $result = $processor->process();

        // Assert

        $this->assertSame($reconciledRoutes, $result);
    }
}
