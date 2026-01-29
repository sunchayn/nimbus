<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions;

use cebe\openapi\Reader;
use Generator;
use Mockery;
use Mockery\LegacyMockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractOpenApiRoutesAction;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(ExtractOpenApiRoutesAction::class)]
class ExtractOpenApiRoutesActionUnitTest extends TestCase
{
    private ExtractOpenApiRoutesAction $extractOpenApiRoutesAction;

    private LegacyMockInterface&ActiveApplicationResolver $activeApplicationResolverMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeApplicationResolverMock = Mockery::mock(ActiveApplicationResolver::class);

        $this->extractOpenApiRoutesAction = new ExtractOpenApiRoutesAction($this->activeApplicationResolverMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_prepends_prefix_to_unprefixed_routes(): void
    {
        // Arrange

        $yaml = <<<'YAML'
openapi: 3.0.0
paths:
  /api/users:
    get:
      responses:
        '200': { description: OK }
  /admin/dashboard:
    get:
      responses:
        '200': { description: OK }
YAML;
        $openapi = Reader::readFromYaml($yaml);

        $this->activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn('api');
        $this->activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(true);

        // Act

        $routes = $this->extractOpenApiRoutesAction->execute($openapi, 'v1');

        // Assert

        $this->assertCount(2, $routes);
        $this->assertEquals('api/v1/users', $routes[0]->uri->value);
        $this->assertEquals('api/v1/admin/dashboard', $routes[1]->uri->value);
    }

    public function test_it_handles_unversioned_routes(): void
    {
        // Arrange

        $yaml = <<<'YAML'
openapi: 3.0.0
paths:
  /api/users:
    get:
      responses:
        '200': { description: OK }
YAML;
        $openapi = Reader::readFromYaml($yaml);

        $this->activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn('api');
        $this->activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(false);

        // Act

        $routes = $this->extractOpenApiRoutesAction->execute($openapi, 'v1');

        // Assert

        $this->assertCount(1, $routes);
        $this->assertEquals('api/users', $routes[0]->uri->value);
    }

    #[DataProvider('schemaDataProvider')]
    public function test_it_extracts_request_body_schema(string $yamlSchema, string $expectedClass, array $expectedAttributes): void
    {
        // Arrange

        $yaml = <<<YAML
openapi: 3.0.0
paths:
  /api/test:
    post:
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                field:
                  $yamlSchema
YAML;
        $openapi = Reader::readFromYaml($yaml);

        $this->activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn('api');
        $this->activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(true);

        // Act

        $routes = $this->extractOpenApiRoutesAction->execute($openapi, 'v1');

        // Assert

        $this->assertCount(1, $routes);
        $schema = $routes[0]->schema;
        $this->assertCount(1, $schema->properties);

        $property = $schema->properties[0];
        $this->assertInstanceOf($expectedClass, $property);
        $this->assertEquals('field', $property->getName());

        $jsonSchema = $property->toJsonSchema();
        foreach ($expectedAttributes as $attribute => $value) {
            if ($attribute === 'stringFormat') {
                $this->assertEquals($value->value, $jsonSchema['format'] ?? null);
            } else {
                $this->assertEquals($value, $jsonSchema[$attribute] ?? null);
            }
        }
    }

    public static function schemaDataProvider(): Generator
    {
        yield 'string property' => [
            'yamlSchema' => "type: string\n                  format: email\n                  minLength: 5",
            'expectedClass' => StringSchemaProperty::class,
            'expectedAttributes' => [
                'stringFormat' => StringFormat::EMAIL,
                'minLength' => 5,
            ],
        ];

        yield 'integer property' => [
            'yamlSchema' => "type: integer\n                  minimum: 0\n                  maximum: 100",
            'expectedClass' => IntegerSchemaProperty::class,
            'expectedAttributes' => [
                'minimum' => 0,
                'maximum' => 100,
            ],
        ];

        yield 'boolean property' => [
            'yamlSchema' => 'type: boolean',
            'expectedClass' => BooleanSchemaProperty::class,
            'expectedAttributes' => [],
        ];

        yield 'number property' => [
            'yamlSchema' => "type: number\n                  minimum: 0.5",
            'expectedClass' => NumberSchemaProperty::class,
            'expectedAttributes' => [
                'minimum' => 0.5,
            ],
        ];

        yield 'array property' => [
            'yamlSchema' => "type: array\n                  items: { type: string }\n                  minItems: 1\n                  maxItems: 5",
            'expectedClass' => ArraySchemaProperty::class,
            'expectedAttributes' => [
                'minItems' => 1,
                'maxItems' => 5,
            ],
        ];
    }

    public function test_it_handles_nested_schemas(): void
    {
        // Arrange

        $yaml = <<<'YAML'
openapi: 3.0.0
paths:
  /api/nested:
    post:
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                user:
                  type: object
                  properties:
                    name: { type: string }
                    tags:
                      type: array
                      items: { type: string }
YAML;
        $openapi = Reader::readFromYaml($yaml);

        $this->activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn('api');
        $this->activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(true);

        // Act

        $routes = $this->extractOpenApiRoutesAction->execute($openapi, 'v1');

        // Assert

        $properties = $routes[0]->schema->properties;

        $this->assertCount(1, $properties);
        $userProp = $properties[0];
        $this->assertInstanceOf(ObjectSchemaProperty::class, $userProp);

        $jsonSchema = $userProp->toJsonSchema();
        $this->assertEquals('object', $jsonSchema['type']);

        // Check for nested properties via object accessor
        $nestedSchema = $userProp->getPropertiesSchema();
        $this->assertNotNull($nestedSchema);

        $nestedProperties = $nestedSchema->properties;

        $tagsProp = collect($nestedProperties)->first(fn (SchemaPropertyInterface $property) => $property->getName() === 'tags');
        $this->assertInstanceOf(ArraySchemaProperty::class, $tagsProp);
        $this->assertEquals('array', $tagsProp->toJsonSchema()['type']);
    }

    public function test_it_extracts_operation_id(): void
    {
        // Arrange

        $yaml = <<<'YAML'
openapi: 3.0.0
paths:
  /api/users:
    get:
      operationId: getAllUsers
      responses:
        '200': { description: OK }
YAML;
        $openapi = Reader::readFromYaml($yaml);

        $this->activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn('api');
        $this->activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(true);

        // Act

        $routes = $this->extractOpenApiRoutesAction->execute($openapi, 'v1');

        // Assert

        $this->assertCount(1, $routes);
        $this->assertEquals('getAllUsers', $routes[0]->metadata['operationId']);
    }

    public function test_it_handles_openapi_3_1_schemas(): void
    {
        // Arrange

        $yaml = <<<'YAML'
openapi: 3.1.0
paths:
  /api/users:
    get:
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                id:
                  type: integer
                name:
                   type: [string, null]
YAML;
        $openapi = Reader::readFromYaml($yaml);

        $this->activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn('api');
        $this->activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(true);

        // Act

        $routes = $this->extractOpenApiRoutesAction->execute($openapi, 'v1');

        // Assert

        $this->assertCount(1, $routes);
        $this->assertEquals('api/v1/users', $routes[0]->uri->value);

        $schema = $routes[0]->schema;
        $this->assertCount(2, $schema->properties);

        $nameProp = collect($schema->properties)->first(fn ($property) => $property->getName() === 'name');

        $this->assertNotNull($nameProp);
    }
}
