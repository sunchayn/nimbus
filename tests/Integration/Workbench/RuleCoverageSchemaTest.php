<?php

namespace Sunchayn\Nimbus\Tests\Integration\Workbench;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\RequestSchemaExtractor;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RequestSchemaExtractor::class)]
class RuleCoverageSchemaTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->prefix('demo')->group(__DIR__.'/../../../workbench/routes/demo.php');
    }

    public function test_it_extracts_the_rule_coverage_demo_request(): void
    {
        // Act

        $schema = $this->extractSchema('demo/shapes/rule-coverage');

        // Assert

        $properties = $schema['properties'];

        $this->assertSame(['role', 'name', 'birthday', 'starts_at', 'slug', 'pin', 'password', 'password_confirmation', 'tags', 'author_id', 'email'], $schema['required']);
        $this->assertSame(['string', 'null'], $properties['nickname']['type']);
        $this->assertSame('required if role is admin', $properties['company']['description']);
        $this->assertSame('uri', $properties['website']['format']);
        $this->assertSame('ipv4', $properties['server_ip']['format']);
        $this->assertSame('date', $properties['birthday']['format']);
        $this->assertArrayNotHasKey('format', $properties['starts_at']);
        $this->assertSame('Y-m-d H:i', $properties['starts_at']['dateFormat']);
        $this->assertSame('^[a-z0-9-]+$', $properties['slug']['pattern']);
        $this->assertSame('^\d{4}$', $properties['pin']['pattern']);
        $this->assertTrue($properties['internal_note']['skipAutofill']);
        $this->assertTrue($properties['tags']['uniqueItems']);
        $this->assertSame(['table' => 'users', 'column' => 'id'], $properties['author_id']['lookup']);
        $this->assertArrayNotHasKey('lookup', $properties['email']);
        $this->assertSame('binary', $properties['avatar']['format']);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSchema(string $uri): array
    {
        $route = collect($this->app->make('router')->getRoutes()->getRoutes())
            ->first(fn (Route $route): bool => $route->uri() === $uri);

        return $this->app
            ->make(RequestSchemaExtractor::class)
            ->extract($route)
            ->toJsonSchema();
    }
}
