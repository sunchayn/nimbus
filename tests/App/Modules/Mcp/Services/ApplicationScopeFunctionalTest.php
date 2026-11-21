<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\ApplicationScope;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ApplicationScope::class)]
class ApplicationScopeFunctionalTest extends TestCase
{
    public function test_it_runs_the_callback_with_the_application_active(): void
    {
        // Arrange

        $scope = resolve(ApplicationScope::class);

        // Act

        $keys = $scope->run(
            'other',
            fn (ActiveApplicationResolver $resolver): array => [
                $resolver->getActiveApplicationKey(),
                resolve(ActiveApplicationResolver::class)->getActiveApplicationKey(),
            ],
        );

        // Assert

        $this->assertSame(
            ['other', 'other'],
            $keys,
        );
    }

    public function test_it_uses_the_default_application_when_none_is_given(): void
    {
        // Arrange

        $scope = resolve(ApplicationScope::class);

        config()->set('nimbus.default_application', 'other');

        // Act

        $key = $scope->run(
            null,
            fn (ActiveApplicationResolver $resolver): string => $resolver->getActiveApplicationKey(),
        );

        // Assert

        $this->assertSame(
            'other',
            $key,
        );
    }

    public function test_it_puts_the_container_back_when_the_callback_fails(): void
    {
        // Arrange

        $scope = resolve(ApplicationScope::class);

        $manifestManager = resolve(ManifestManager::class);

        // Act

        try {
            $scope->run('other', function (): never {
                throw new RuntimeException('Failed.');
            });
        } catch (RuntimeException) {
            // Expected.
        }

        // Assert

        $this->assertSame(
            $manifestManager,
            resolve(ManifestManager::class),
        );

        $this->assertSame(
            'main',
            resolve(ActiveApplicationResolver::class)->getActiveApplicationKey(),
        );
    }

    public function test_it_rejects_an_unknown_application(): void
    {
        // Arrange

        $scope = resolve(ApplicationScope::class);

        // Assert

        $this->expectException(McpToolException::class);
        $this->expectExceptionMessage('The application `nope` is not configured.');

        // Act

        $scope->run('nope', fn (): null => null);
    }
}
