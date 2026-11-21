<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Mcp\Actions\DescribeRouteAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DescribeRouteAction::class)]
class DescribeRouteActionFunctionalTest extends TestCase
{
    use FakesExtractedRoutes;

    public function test_it_returns_the_route_schema(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(DescribeRouteAction::class);

        // Act

        $description = $action->execute('users.store');

        // Assert

        $this->assertSame(
            'POST api/users',
            $description['id'],
        );

        $this->assertSame(
            ['email', 'name'],
            $description['schema']['required'],
        );

        $this->assertNull($description['extractionError']);
    }

    public function test_it_lists_the_generated_headers_of_the_application(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications.main.headers', [
            'X-App' => '1.0',
            'X-Request-Id' => '$uuid',
        ]);

        $action = resolve(DescribeRouteAction::class);

        // Act

        $description = $action->execute('users.store');

        // Assert

        $this->assertSame(
            ['X-Request-Id' => '$uuid'],
            $description['generatedHeaders'],
        );
    }

    public function test_it_fails_for_an_unknown_route(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(DescribeRouteAction::class);

        // Assert

        $this->expectException(RouteNotFoundException::class);

        // Act

        $action->execute('GET nowhere');
    }

    public function test_it_fails_as_ambiguous_when_several_applications_have_the_route(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        config()->set('nimbus.applications', [
            'main' => config('nimbus.applications.main'),
            'other' => config('nimbus.applications.main'),
        ]);

        $action = resolve(DescribeRouteAction::class);

        // Act

        try {
            $action->execute('users.store');

            $this->fail('The reference should be ambiguous.');
        } catch (McpToolException $mcpToolException) {
            $this->assertSame(
                McpToolException::ROUTE_AMBIGUOUS,
                $mcpToolException->errorCode,
            );

            $this->assertStringContainsString(
                '`main`, `other`',
                $mcpToolException->getMessage(),
            );

            $this->assertSame(
                'Pass `application` with one of them.',
                $mcpToolException->hint,
            );
        }

        $description = $action->execute('users.store', application: 'other');

        // Assert

        $this->assertSame(
            'other',
            $description['application'],
        );
    }

    public function test_it_fails_for_an_unknown_application(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(DescribeRouteAction::class);

        // Assert

        $this->expectException(McpToolException::class);
        $this->expectExceptionMessage('The application `nope` is not configured.');

        // Act

        $action->execute('users.store', application: 'nope');
    }

    public function test_it_reports_a_rules_extraction_error_as_plain_text(): void
    {
        // Arrange

        $route = new ExtractedRoute(
            new Endpoint('v1', 'broken', '/api/broken'),
            ['POST'],
            new Schema([], new RulesExtractionError(new RuntimeException('Rules could not be read'))),
        );

        config()->set('nimbus.applications', ['main' => config('nimbus.applications.main')]);

        $this->mock(ExtractRoutesAction::class)
            ->shouldReceive('execute')
            ->andReturn(new ExtractedRoutesCollection([$route]));

        $action = resolve(DescribeRouteAction::class);

        // Act

        $description = $action->execute('POST api/broken');

        // Assert

        $this->assertStringContainsString(
            'Rules could not be read',
            $description['extractionError'],
        );

        $this->assertStringNotContainsString(
            '<b>',
            $description['extractionError'],
        );
    }
}
