<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Services\SchemaPayloadValidator;

#[CoversClass(SchemaPayloadValidator::class)]
class SchemaPayloadValidatorUnitTest extends TestCase
{
    public function test_it_accepts_a_valid_payload_and_nullable_types(): void
    {
        // Arrange

        $schema = [
            'type' => 'object',
            'properties' => ['a' => ['type' => ['string', 'null']]],
        ];

        $validator = new SchemaPayloadValidator;

        // Act

        $errors = $validator->validate(
            $schema,
            ['a' => null],
        );

        // Assert

        $this->assertSame(
            [],
            $errors,
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    #[DataProvider('failingCases')]
    public function test_it_reports_the_failed_rule(array $schema, mixed $payload, string $path, string $rule): void
    {
        // Arrange

        $validator = new SchemaPayloadValidator;

        // Act

        $errors = $validator->validate(
            $schema,
            $payload,
        );

        // Assert

        $failures = array_map(
            fn (array $error): array => ['path' => $error['path'], 'rule' => $error['rule']],
            $errors,
        );

        $this->assertSame(
            [['path' => $path, 'rule' => $rule]],
            $failures,
        );
    }

    public static function failingCases(): Generator
    {
        $object = [
            'type' => 'object',
            'required' => ['a'],
            'additionalProperties' => false,
            'properties' => [
                'a' => ['type' => 'string', 'format' => 'email'],
                'n' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                'l' => ['type' => 'array', 'minItems' => 1, 'items' => ['type' => 'string']],
                'e' => ['enum' => ['x', 'y']],
            ],
        ];

        yield 'missing required' => [
            'schema' => $object,
            'payload' => ['n' => 1],
            'path' => 'a',
            'rule' => 'required',
        ];

        yield 'unknown field' => [
            'schema' => $object,
            'payload' => ['a' => 'a@b.co', 'z' => 1],
            'path' => 'z',
            'rule' => 'additionalProperties',
        ];

        yield 'wrong type' => [
            'schema' => $object,
            'payload' => ['a' => 5],
            'path' => 'a',
            'rule' => 'type',
        ];

        yield 'too short' => [
            'schema' => ['type' => 'string', 'minLength' => 3],
            'payload' => 'ab',
            'path' => '',
            'rule' => 'minLength',
        ];

        yield 'too long' => [
            'schema' => ['type' => 'string', 'maxLength' => 1],
            'payload' => 'ab',
            'path' => '',
            'rule' => 'maxLength',
        ];

        yield 'bad format' => [
            'schema' => $object,
            'payload' => ['a' => 'nope'],
            'path' => 'a',
            'rule' => 'format',
        ];

        yield 'below minimum' => [
            'schema' => $object,
            'payload' => ['a' => 'a@b.co', 'n' => 0],
            'path' => 'n',
            'rule' => 'minimum',
        ];

        yield 'above maximum' => [
            'schema' => $object,
            'payload' => ['a' => 'a@b.co', 'n' => 6],
            'path' => 'n',
            'rule' => 'maximum',
        ];

        yield 'bad item' => [
            'schema' => $object,
            'payload' => ['a' => 'a@b.co', 'l' => ['ok', 3]],
            'path' => 'l.1',
            'rule' => 'type',
        ];

        yield 'bad enum' => [
            'schema' => $object,
            'payload' => ['a' => 'a@b.co', 'e' => 'q'],
            'path' => 'e',
            'rule' => 'enum',
        ];

        yield 'no branch matches' => [
            'schema' => ['anyOf' => [['type' => 'string'], ['type' => 'integer']]],
            'payload' => true,
            'path' => '',
            'rule' => 'anyOf',
        ];

        yield 'wrong const' => [
            'schema' => ['const' => 'fixed'],
            'payload' => 'other',
            'path' => '',
            'rule' => 'const',
        ];

        yield 'pattern mismatch' => [
            'schema' => ['type' => 'string', 'pattern' => '^a/b$'],
            'payload' => 'abc',
            'path' => '',
            'rule' => 'pattern',
        ];

        yield 'at exclusive minimum' => [
            'schema' => ['type' => 'integer', 'exclusiveMinimum' => 3],
            'payload' => 3,
            'path' => '',
            'rule' => 'exclusiveMinimum',
        ];

        yield 'at exclusive maximum' => [
            'schema' => ['type' => 'integer', 'exclusiveMaximum' => 3],
            'payload' => 3,
            'path' => '',
            'rule' => 'exclusiveMaximum',
        ];

        yield 'too few items' => [
            'schema' => ['type' => 'array', 'minItems' => 2],
            'payload' => ['a'],
            'path' => '',
            'rule' => 'minItems',
        ];

        yield 'too many items' => [
            'schema' => ['type' => 'array', 'maxItems' => 1],
            'payload' => ['a', 'b'],
            'path' => '',
            'rule' => 'maxItems',
        ];

        yield 'all of branch fails' => [
            'schema' => ['allOf' => [['type' => 'string'], ['type' => 'string', 'minLength' => 5]]],
            'payload' => 'abc',
            'path' => '',
            'rule' => 'minLength',
        ];

        yield 'one of matches several branches' => [
            'schema' => ['oneOf' => [['type' => 'string'], ['type' => 'string', 'minLength' => 1]]],
            'payload' => 'abc',
            'path' => '',
            'rule' => 'oneOf',
        ];

        yield 'one of matches no branch' => [
            'schema' => ['oneOf' => [['type' => 'integer'], ['type' => 'boolean']]],
            'payload' => 'abc',
            'path' => '',
            'rule' => 'oneOf',
        ];

        yield 'bad url' => [
            'schema' => ['type' => 'string', 'format' => 'url'],
            'payload' => 'not a url',
            'path' => '',
            'rule' => 'format',
        ];

        yield 'bad ipv4' => [
            'schema' => ['type' => 'string', 'format' => 'ipv4'],
            'payload' => '::1',
            'path' => '',
            'rule' => 'format',
        ];

        yield 'bad ipv6' => [
            'schema' => ['type' => 'string', 'format' => 'ipv6'],
            'payload' => '127.0.0.1',
            'path' => '',
            'rule' => 'format',
        ];

        yield 'bad uuid' => [
            'schema' => ['type' => 'string', 'format' => 'uuid'],
            'payload' => 'not-a-uuid',
            'path' => '',
            'rule' => 'format',
        ];

        yield 'bad date' => [
            'schema' => ['type' => 'string', 'format' => 'date'],
            'payload' => '2026-13-45',
            'path' => '',
            'rule' => 'format',
        ];

        yield 'bad date time' => [
            'schema' => ['type' => 'string', 'format' => 'date-time'],
            'payload' => 'yesterday-ish',
            'path' => '',
            'rule' => 'format',
        ];
    }

    public function test_it_accepts_values_the_schema_does_not_constrain(): void
    {
        // Arrange

        $validator = new SchemaPayloadValidator;

        $schema = [
            'type' => 'object',
            'properties' => [
                'pattern' => ['type' => 'string', 'pattern' => '('],
                'format' => ['type' => 'string', 'format' => 'custom'],
                'list' => ['type' => 'array'],
                'uuid' => ['type' => 'string', 'format' => 'uuid'],
                'date' => ['type' => 'string', 'format' => 'date'],
                'url' => ['type' => 'string', 'format' => 'uri'],
                'ipv4' => ['type' => 'string', 'format' => 'ipv4'],
                'ipv6' => ['type' => 'string', 'format' => 'ipv6'],
                'time' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];

        $payload = [
            'pattern' => 'anything',
            'format' => 'anything',
            'list' => ['a', 'b'],
            'uuid' => '123e4567-e89b-42d3-a456-426614174000',
            'date' => '2026-01-01',
            'url' => 'https://example.com',
            'ipv4' => '127.0.0.1',
            'ipv6' => '::1',
            'time' => '2026-01-01T00:00:00Z',
        ];

        // Act

        $errors = $validator->validate($schema, $payload);

        // Assert

        $this->assertSame([], $errors);
    }
}
