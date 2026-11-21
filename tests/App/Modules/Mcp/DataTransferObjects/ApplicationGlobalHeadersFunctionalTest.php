<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ApplicationGlobalHeaders;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ApplicationGlobalHeaders::class)]
class ApplicationGlobalHeadersFunctionalTest extends TestCase
{
    public function test_it_splits_the_headers_by_how_their_value_is_made(): void
    {
        // Arrange

        config()->set('nimbus.applications.main.headers', [
            'X-App' => '1.0',
            'X-Retries' => 3,
            'X-Id' => '$uuid',
            'X-Mail' => ' $Email ',
            'X-Rand' => '$string',
            'X-Nested' => ['a'],
        ]);

        // Act

        $headers = ApplicationGlobalHeaders::fromResolver(resolve(ActiveApplicationResolver::class));

        // Assert

        $this->assertSame(
            ['X-App' => '1.0', 'X-Retries' => '3'],
            $headers->fixed,
        );

        $this->assertSame(
            ['X-Id' => '$uuid', 'X-Mail' => '$email', 'X-Rand' => '$string'],
            $headers->generated,
        );
    }

    public function test_it_turns_into_an_array(): void
    {
        // Arrange

        $headers = new ApplicationGlobalHeaders(
            fixed: ['X-App' => '1.0'],
            generated: ['X-Id' => '$uuid'],
        );

        // Act

        $array = $headers->toArray();

        // Assert

        $this->assertSame(
            ['fixed' => ['X-App' => '1.0'], 'generated' => ['X-Id' => '$uuid']],
            $array,
        );
    }
}
