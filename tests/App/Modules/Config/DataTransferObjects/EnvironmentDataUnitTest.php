<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Config\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Config\DataTransferObjects\EnvironmentData;
use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentSourceEnum;

#[CoversClass(EnvironmentData::class)]
class EnvironmentDataUnitTest extends TestCase
{
    public function test_it_converts_to_an_array(): void
    {
        // Arrange

        $environmentData = new EnvironmentData(
            id: 'staging',
            name: 'Staging',
            baseUrl: 'https://staging.example.com',
            enabled: true,
            source: EnvironmentSourceEnum::Config,
            detected: 'staging',
        );

        // Act

        $result = $environmentData->toArray();

        // Assert

        $this->assertSame([
            'id' => 'staging',
            'name' => 'Staging',
            'baseUrl' => 'https://staging.example.com',
            'enabled' => true,
            'source' => 'config',
            'detected' => 'staging',
        ], $result);
    }

    public function test_it_defaults_the_detected_value_to_null(): void
    {
        // Arrange

        $environmentData = new EnvironmentData(
            id: 'current',
            name: 'Local',
            baseUrl: 'http://localhost',
            enabled: false,
            source: EnvironmentSourceEnum::Current,
        );

        // Act

        $result = $environmentData->toArray();

        // Assert

        $this->assertNull($result['detected']);
        $this->assertFalse($result['enabled']);
        $this->assertSame('current', $result['source']);
    }
}
