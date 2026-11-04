<?php

namespace Sunchayn\Nimbus\Tests\Integration\Workbench;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\RequestSchemaExtractor;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RequestSchemaExtractor::class)]
class JsonUploadSchemaTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->prefix('demo')->group(__DIR__.'/../../../workbench/routes/demo.php');
    }

    public function test_it_marks_the_single_file_content_as_base64(): void
    {
        // Act

        $properties = $this->extractProperties('demo/uploads/json/single')['file']['properties'];

        // Assert

        $this->assertSame('base64', $properties['content']['contentEncoding']);
        $this->assertArrayNotHasKey('contentEncoding', $properties['name']);
        $this->assertArrayNotHasKey('contentEncoding', $properties['mime_type']);
    }

    public function test_it_marks_the_content_of_every_file_as_base64(): void
    {
        // Act

        $properties = $this->extractProperties('demo/uploads/json/multiple')['files']['items']['properties'];

        // Assert

        $this->assertSame('base64', $properties['content']['contentEncoding']);
        $this->assertArrayNotHasKey('contentEncoding', $properties['name']);
        $this->assertArrayNotHasKey('contentEncoding', $properties['mime_type']);
    }

    public function test_it_keeps_the_size_rules_of_the_files_array(): void
    {
        // Act

        $files = $this->extractProperties('demo/uploads/json/multiple')['files'];

        // Assert

        $this->assertSame(1, $files['minItems']);
        $this->assertSame(3, $files['maxItems']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function extractProperties(string $uri): array
    {
        $route = collect($this->app->make('router')->getRoutes()->getRoutes())
            ->first(fn (Route $route): bool => $route->uri() === $uri);

        return $this->app
            ->make(RequestSchemaExtractor::class)
            ->extract($route)
            ->toJsonSchema()['properties'];
    }
}
