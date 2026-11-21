<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Services\SchemaPayloadBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Services\SchemaPayloadValidator;

#[CoversClass(SchemaPayloadBuilder::class)]
class SchemaPayloadBuilderUnitTest extends TestCase
{
    public function test_it_builds_a_valid_payload_for_every_field(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        // Act

        $result = $builder->build(
            $this->schema(),
            'valid',
        );

        // Assert

        $this->assertSame(
            'user@example.com',
            $result['payload']['email'],
        );

        $this->assertSame(
            18,
            $result['payload']['age'],
        );

        $this->assertSame(
            'admin',
            $result['payload']['role'],
        );

        $this->assertSame(
            [1, 1],
            $result['payload']['items'],
        );

        $this->assertGreaterThanOrEqual(
            8,
            strlen($result['payload']['profile']['bio']),
        );

        $this->assertSame(
            [],
            (new SchemaPayloadValidator)->validate($this->schema(), $result['payload']),
        );
    }

    public function test_it_builds_only_required_fields_in_minimal_mode(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        // Act

        $result = $builder->build(
            $this->schema(),
            'minimal',
        );

        // Assert

        $this->assertSame(
            ['email', 'items'],
            array_keys($result['payload']),
        );
    }

    public function test_it_applies_dotted_overrides(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        $overrides = ['profile.bio' => 'hello world', 'age' => 40];

        // Act

        $result = $builder->build(
            $this->schema(),
            'valid',
            $overrides,
        );

        // Assert

        $this->assertSame(
            'hello world',
            $result['payload']['profile']['bio'],
        );

        $this->assertSame(
            40,
            $result['payload']['age'],
        );
    }

    /**
     * @param  array<string, mixed>  $property
     */
    #[DataProvider('propertyProvider')]
    public function test_it_builds_a_value_for_a_property(array $property, mixed $expected): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        $schema = [
            'type' => 'object',
            'properties' => ['field' => $property],
            'required' => ['field'],
        ];

        // Act

        $result = $builder->build($schema, 'valid');

        // Assert

        $this->assertSame(
            $expected,
            $result['payload']['field'],
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, mixed}>
     */
    public static function propertyProvider(): array
    {
        return [
            'const' => [['const' => 'fixed'], 'fixed'],
            'boolean' => [['type' => 'boolean'], true],
            'number minimum' => [['type' => 'number', 'minimum' => 2.5], 2.5],
            'exclusive minimum' => [['type' => 'number', 'exclusiveMinimum' => 0], 1.0],
            'minimum above maximum' => [['type' => 'integer', 'minimum' => 5, 'maximum' => 3], 3],
            'nullable type list' => [['type' => ['null', 'string']], 'example'],
            'uuid' => [['type' => 'string', 'format' => 'uuid'], '123e4567-e89b-42d3-a456-426614174000'],
            'uri' => [['type' => 'string', 'format' => 'uri'], 'https://example.com'],
            'url' => [['type' => 'string', 'format' => 'url'], 'https://example.com'],
            'date' => [['type' => 'string', 'format' => 'date'], '2026-01-01'],
            'date time' => [['type' => 'string', 'format' => 'date-time'], '2026-01-01T00:00:00Z'],
            'ipv4' => [['type' => 'string', 'format' => 'ipv4'], '127.0.0.1'],
            'max length' => [['type' => 'string', 'maxLength' => 3], 'exa'],
            'min length' => [['type' => 'string', 'minLength' => 9], 'exampleaa'],
        ];
    }

    public function test_it_skips_a_property_that_is_not_a_schema(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        $schema = [
            'type' => 'object',
            'properties' => ['broken' => 'junk', 'name' => ['type' => 'string']],
        ];

        // Act

        $result = $builder->build($schema, 'valid');

        // Assert

        $this->assertSame(
            ['name' => 'example'],
            $result['payload'],
        );
    }

    public function test_it_picks_the_existing_value_that_fits_the_type_of_the_property(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        $schema = [
            'type' => 'object',
            'properties' => [
                'weight' => ['type' => 'number', 'lookup' => ['table' => 'items', 'column' => 'weight']],
                'code' => ['type' => 'string', 'lookup' => ['table' => 'items', 'column' => 'code']],
                'count' => ['type' => 'integer', 'lookup' => ['table' => 'items', 'column' => 'weight']],
            ],
            'required' => ['weight', 'code', 'count'],
        ];

        // Act

        $result = $builder->build(
            schema: $schema,
            mode: 'valid',
            lookupValues: [
                'items.weight' => ['abc', '3.5', 4],
                'items.code' => [77],
            ],
        );

        // Assert

        $this->assertSame(3.5, $result['payload']['weight']);

        $this->assertSame('77', $result['payload']['code']);

        $this->assertSame(4, $result['payload']['count']);
    }

    public function test_it_changes_the_type_of_the_first_field_when_no_field_is_required(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        $schema = [
            'type' => 'object',
            'properties' => ['name' => ['type' => 'string']],
        ];

        // Act

        $result = $builder->build($schema, 'invalid');

        // Assert

        $this->assertSame(
            ['unexpected' => true],
            $result['payload']['name'],
        );

        $this->assertContains(
            'The field `name` has the wrong type.',
            $result['notes'],
        );
    }

    public function test_it_adds_an_unknown_field_when_the_schema_has_no_fields(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        // Act

        $result = $builder->build(['type' => 'object'], 'invalid');

        // Assert

        $this->assertTrue($result['payload']['unknown_field']);

        $this->assertContains(
            'An unknown field was added.',
            $result['notes'],
        );
    }

    public function test_it_breaks_one_rule_in_invalid_mode(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        // Act

        $result = $builder->build(
            $this->schema(),
            'invalid',
        );

        // Assert

        $this->assertNotSame(
            [],
            (new SchemaPayloadValidator)->validate($this->schema(), $result['payload']),
        );

        $this->assertStringContainsString(
            'required field `email`',
            $result['notes'][0],
        );
    }

    public function test_it_notes_fields_it_cannot_fill(): void
    {
        // Arrange

        $schema = [
            'type' => 'object',
            'properties' => [
                'avatar' => ['type' => 'string', 'format' => 'binary'],
                'code' => ['type' => 'string', 'pattern' => '^[A-Z]{3}$'],
            ],
        ];

        $builder = new SchemaPayloadBuilder;

        // Act

        $result = $builder->build(
            $schema,
            'valid',
        );

        // Assert

        $this->assertCount(
            2,
            $result['notes'],
        );
    }

    public function test_it_uses_an_existing_value_for_an_exists_rule(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        $schema = $this->existsSchema();

        // Act

        $result = $builder->build(
            schema: $schema,
            mode: 'valid',
            lookupValues: ['users.id' => ['42', '7']],
        );

        // Assert

        $this->assertSame(
            42,
            $result['payload']['user_id'],
        );

        $this->assertSame(
            [42, 42],
            $result['payload']['user_ids'],
        );

        $this->assertSame(
            [],
            $result['notes'],
        );
    }

    public function test_it_notes_an_exists_rule_without_an_existing_value(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        // Act

        $result = $builder->build(
            schema: $this->existsSchema(),
            mode: 'valid',
        );

        // Assert

        $this->assertSame(
            1,
            $result['payload']['user_id'],
        );

        $this->assertContains(
            '`user_id` must exist in users.id, and no value could be read from it. The value is a placeholder, so set it with `overrides`.',
            $result['notes'],
        );
    }

    public function test_it_notes_an_exists_rule_on_a_model_class(): void
    {
        // Arrange

        $builder = new SchemaPayloadBuilder;

        $schema = [
            'type' => 'object',
            'properties' => [
                'owner_id' => [
                    'type' => 'integer',
                    'description' => 'must exist in App\\Models\\User.id',
                ],
            ],
            'required' => ['owner_id'],
        ];

        // Act

        $result = $builder->build(
            schema: $schema,
            mode: 'valid',
        );

        // Assert

        $this->assertContains(
            '`owner_id` must exist in App\\Models\\User.id, and Nimbus cannot read it from the application. The value is a placeholder, so set it with `overrides`.',
            $result['notes'],
        );
    }

    /*
     * Generators.
     */

    /**
     * @return array<string, mixed>
     */
    private function existsSchema(): array
    {
        $lookup = ['table' => 'users', 'column' => 'id'];

        return [
            'type' => 'object',
            'properties' => [
                'user_id' => ['type' => 'integer', 'lookup' => $lookup],
                'user_ids' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'items' => ['type' => 'integer', 'lookup' => $lookup],
                ],
            ],
            'required' => ['user_id', 'user_ids'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'required' => ['email', 'items'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'age' => ['type' => 'integer', 'minimum' => 18],
                'role' => ['type' => 'string', 'enum' => ['admin', 'user']],
                'items' => ['type' => 'array', 'minItems' => 2, 'items' => ['type' => 'integer']],
                'profile' => [
                    'type' => 'object',
                    'properties' => [
                        'bio' => ['type' => 'string', 'minLength' => 8],
                    ],
                ],
            ],
        ];
    }
}
