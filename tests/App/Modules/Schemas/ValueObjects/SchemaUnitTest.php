<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Generator;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(Schema::class)]
class SchemaUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_builds_empty_schema(): void
    {
        // Act

        $schema = Schema::empty();

        // Assert

        $this->assertEmpty($schema->properties);
        $this->assertNull($schema->extractionError);
    }

    #[DataProvider('isEmptyCheckDataProvider')]
    public function test_checks_if_empty(Schema $schema, bool $expected): void
    {
        // Act

        $isEmpty = $schema->isEmpty();

        // Assert

        $this->assertEquals($expected, $isEmpty);
    }

    public static function isEmptyCheckDataProvider(): Generator
    {
        yield 'Empty schema' => [
            'schema' => new Schema(properties: []),
            'expected' => true,
        ];

        yield 'Empty with extraction error' => [
            'schema' => new Schema(
                properties: [],
                extractionError: new RulesExtractionError(new RuntimeException)
            ),
            'expected' => true,
        ];

        yield 'Non-empty schema' => [
            'schema' => new Schema(properties: [new StringSchemaProperty(name: 'field')]),
            'expected' => false,
        ];
    }

    #[DataProvider('toArrayDataProvider')]
    public function test_converts_to_array(array $properties, array $expected): void
    {
        // Arrange

        $schema = new Schema(properties: $properties);

        // Act

        $result = $schema->toArray();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function toArrayDataProvider(): Generator
    {
        yield 'empty schema' => [
            'properties' => [],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'single property' => [
            'properties' => [
                self::createMockProperty('name', ['type' => 'string']),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'multiple properties' => [
            'properties' => [
                self::createMockProperty('name', ['type' => 'string']),
                self::createMockProperty('age', ['type' => 'integer']),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'age' => ['type' => 'integer'],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'properties with formats' => [
            'properties' => [
                self::createMockProperty('email', ['type' => 'string', 'format' => 'email']),
                self::createMockProperty('uuid', ['type' => 'string', 'format' => 'uuid']),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'uuid' => ['type' => 'string', 'format' => 'uuid'],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'nested object properties' => [
            'properties' => [
                self::createMockProperty('user', [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string', 'format' => 'email'],
                    ],
                ]),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'user' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'email' => ['type' => 'string', 'format' => 'email'],
                        ],
                    ],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'array properties' => [
            'properties' => [
                self::createMockProperty('tags', [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ]),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];
    }

    /**
     * Helper to create mock property for testing.
     */
    private static function createMockProperty(string $name, array $arrayData, bool $required = false): SchemaPropertyInterface
    {
        $mock = Mockery::mock(SchemaPropertyInterface::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('isRequired')->andReturn($required);
        $mock->shouldReceive('toJsonSchema')->andReturn($arrayData);

        return $mock;
    }

    #[DataProvider('toJsonSchemaDataProvider')]
    public function test_converts_to_json_schema(array $properties, array $expected): void
    {
        // Arrange

        $schema = new Schema(properties: $properties);

        // Act

        $result = $schema->toJsonSchema();

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function toJsonSchemaDataProvider(): Generator
    {
        yield 'empty schema' => [
            'properties' => [],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'single non-required property' => [
            'properties' => [
                self::createMockProperty('name', ['type' => 'string']),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'single required property' => [
            'properties' => [
                self::createMockProperty('email', ['type' => 'string', 'format' => 'email'], true),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
                'required' => ['email'],
                'additionalProperties' => false,
            ],
        ];

        yield 'mixed required and optional properties' => [
            'properties' => [
                self::createMockProperty('name', ['type' => 'string'], true),
                self::createMockProperty('age', ['type' => 'integer']),
                self::createMockProperty('email', ['type' => 'string', 'format' => 'email'], true),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'age' => ['type' => 'integer'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
                'required' => ['name', 'email'],
                'additionalProperties' => false,
            ],
        ];

        yield 'all required properties' => [
            'properties' => [
                self::createMockProperty('id', ['type' => 'string', 'format' => 'uuid'], true),
                self::createMockProperty('name', ['type' => 'string'], true),
                self::createMockProperty('email', ['type' => 'string', 'format' => 'email'], true),
            ],
            'expected' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
                'required' => ['id', 'name', 'email'],
                'additionalProperties' => false,
            ],
        ];
    }

    #[DataProvider('fromArrayMapDataProvider')]
    public function test_creates_schema_from_array_map(array $map, array $expectedArray): void
    {
        // Act

        $schema = Schema::fromArrayMap($map);

        // Assert

        $this->assertEquals($expectedArray, $schema->toArray());
    }

    public static function fromArrayMapDataProvider(): Generator
    {
        yield 'map with SchemaPropertyInterface' => [
            'map' => [
                'name' => new StringSchemaProperty('name'),
            ],
            'expectedArray' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
                'required' => [],
                'additionalProperties' => false,
            ],
        ];

        yield 'map with nested Schema' => [
            'map' => [
                'user' => new Schema([new StringSchemaProperty('email')]),
            ],
            'expectedArray' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'user' => [
                        'type' => 'object',
                        'properties' => [
                            'email' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [],
                        'additionalProperties' => false,
                    ],
                ],
                'required' => [
                    'user',
                ],
                'additionalProperties' => false,
            ],
        ];

        yield 'map with mixed entries filtering non-schema items' => [
            'map' => [
                'name' => new StringSchemaProperty('name'),
                'sub' => new Schema([new StringSchemaProperty('title')]),
                'ignored' => 'not_a_schema',
            ],
            'expectedArray' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                    'sub' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [],
                        'additionalProperties' => false,
                    ],
                ],
                'required' => [
                    'sub',
                ],
                'additionalProperties' => false,
            ],
        ];
    }
}
