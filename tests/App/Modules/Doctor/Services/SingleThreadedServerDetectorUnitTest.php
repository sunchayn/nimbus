<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Doctor\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Doctor\Services\SingleThreadedServerDetector;

#[CoversClass(SingleThreadedServerDetector::class)]
class SingleThreadedServerDetectorUnitTest extends TestCase
{
    public function test_it_matches_the_same_host_and_port(): void
    {
        // Arrange

        $detector = new SingleThreadedServerDetector;

        // Act & Assert

        $this->assertTrue($detector->isSameHostAndPort('http://localhost:8000/api/users', 'http://LOCALHOST:8000'));
        $this->assertTrue($detector->isSameHostAndPort('http://example.test', 'http://example.test:80'));
        $this->assertFalse($detector->isSameHostAndPort('http://localhost:8001', 'http://localhost:8000'));
        $this->assertFalse($detector->isSameHostAndPort('/relative', 'http://localhost:8000'));
    }

    public function test_it_suggests_a_second_server_on_the_next_port(): void
    {
        // Arrange

        $detector = new SingleThreadedServerDetector;

        // Act & Assert

        $this->assertSame('http://127.0.0.1:8001', $detector->suggestBaseUrl('http://127.0.0.1:8000/api'));
        $this->assertSame('https://app.test:444', $detector->suggestBaseUrl('https://app.test'));
    }
}
