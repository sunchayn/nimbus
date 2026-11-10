<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services\RulesMapper;

use Generator;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\Services\BinaryContentInferrer;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\PropertyBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\ConditionalRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\FormatRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\LookupRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\RuleToSchemaMapper;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\PropertyAnnotations;

#[CoversClass(RuleToSchemaMapper::class)]
#[CoversClass(ConditionalRuleProcessor::class)]
#[CoversClass(FormatRuleProcessor::class)]
#[CoversClass(LookupRuleProcessor::class)]
#[CoversClass(PropertyAnnotations::class)]
#[CoversClass(SchemaBuilder::class)]
#[CoversClass(PropertyBuilder::class)]
class RuleCoverageUnitTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $expected
     */
    #[DataProvider('propertySchemaProvider')]
    public function test_it_maps_rules_to_a_property_schema(string $rules, array $expected): void
    {
        // Act

        $property = $this->buildSchema(['field' => $rules])['properties']['field'];

        // Assert

        $this->assertEquals($expected, $property);
    }

    public static function propertySchemaProvider(): Generator
    {
        yield 'nullable' => [
            'rules' => 'nullable|string',
            'expected' => ['type' => ['string', 'null']],
        ];

        yield 'regex that ECMAScript reads the same way' => [
            'rules' => 'string|regex:/^[a-z]+$/',
            'expected' => ['type' => 'string', 'pattern' => '^[a-z]+$'],
        ];

        yield 'regex with a PCRE only construct' => [
            'rules' => 'string|regex:/^(?i)abc$/',
            'expected' => ['type' => 'string', 'description' => 'matches the regex /^(?i)abc$/'],
        ];

        yield 'regex with a flag' => [
            'rules' => 'string|regex:/^abc$/i',
            'expected' => ['type' => 'string', 'description' => 'matches the regex /^abc$/i'],
        ];

        yield 'url' => [
            'rules' => 'string|url',
            'expected' => ['type' => 'string', 'format' => 'uri'],
        ];

        yield 'ipv4' => [
            'rules' => 'ipv4',
            'expected' => ['type' => 'string', 'format' => 'ipv4'],
        ];

        yield 'ip' => [
            'rules' => 'ip',
            'expected' => ['type' => 'string', 'description' => 'IPv4 or IPv6 address'],
        ];

        yield 'date format with a date only' => [
            'rules' => 'date_format:Y-m-d',
            'expected' => ['type' => 'string', 'format' => 'date', 'description' => 'date format Y-m-d', 'dateFormat' => 'Y-m-d'],
        ];

        yield 'date format with an iso time' => [
            'rules' => 'date_format:c',
            'expected' => ['type' => 'string', 'format' => 'date-time', 'description' => 'date format c', 'dateFormat' => 'c'],
        ];

        yield 'date format with an iso time and offset' => [
            'rules' => 'date_format:Y-m-d\TH:i:sP',
            'expected' => ['type' => 'string', 'format' => 'date-time', 'description' => 'date format Y-m-d\TH:i:sP', 'dateFormat' => 'Y-m-d\TH:i:sP'],
        ];

        yield 'date format with an iso time in milliseconds' => [
            'rules' => 'date_format:Y-m-d\TH:i:s.v\Z',
            'expected' => ['type' => 'string', 'format' => 'date-time', 'description' => 'date format Y-m-d\TH:i:s.v\Z', 'dateFormat' => 'Y-m-d\TH:i:s.v\Z'],
        ];

        yield 'date format with a custom date' => [
            'rules' => 'date_format:d/m/Y',
            'expected' => ['type' => 'string', 'description' => 'date format d/m/Y', 'dateFormat' => 'd/m/Y'],
        ];

        yield 'date format with a custom time' => [
            'rules' => 'date_format:Y-m-d H:i',
            'expected' => ['type' => 'string', 'description' => 'date format Y-m-d H:i', 'dateFormat' => 'Y-m-d H:i'],
        ];

        yield 'alpha' => [
            'rules' => 'alpha',
            'expected' => ['type' => 'string', 'pattern' => '^[\p{L}\p{M}]+$'],
        ];

        yield 'alpha with ascii' => [
            'rules' => 'alpha:ascii',
            'expected' => ['type' => 'string', 'pattern' => '^[A-Za-z]+$'],
        ];

        yield 'alpha num with ascii' => [
            'rules' => 'alpha_num:ascii',
            'expected' => ['type' => 'string', 'pattern' => '^[A-Za-z0-9]+$'],
        ];

        yield 'alpha dash' => [
            'rules' => 'alpha_dash',
            'expected' => ['type' => 'string', 'pattern' => '^[\p{L}\p{M}\p{N}_-]+$'],
        ];

        yield 'between on a string' => [
            'rules' => 'string|between:2,5',
            'expected' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 5],
        ];

        yield 'between on an integer' => [
            'rules' => 'integer|between:2,5',
            'expected' => ['type' => 'integer', 'minimum' => 2, 'maximum' => 5],
        ];

        yield 'digits on a string' => [
            'rules' => 'digits:4',
            'expected' => ['type' => 'string', 'pattern' => '^\d{4}$'],
        ];

        yield 'digits between on a string' => [
            'rules' => 'digits_between:4,6',
            'expected' => ['type' => 'string', 'pattern' => '^\d{4,6}$'],
        ];

        yield 'digits on an integer' => [
            'rules' => 'integer|digits:3',
            'expected' => ['type' => 'integer', 'minimum' => 100, 'maximum' => 999],
        ];

        yield 'distinct' => [
            'rules' => 'array|distinct',
            'expected' => ['type' => 'array', 'uniqueItems' => true],
        ];

        yield 'mimes' => [
            'rules' => 'file|mimes:jpg,png',
            'expected' => ['type' => 'string', 'format' => 'binary', 'description' => 'accepted types jpg, png'],
        ];

        yield 'image' => [
            'rules' => 'image',
            'expected' => ['type' => 'string', 'format' => 'binary', 'description' => 'image file'],
        ];

        yield 'exists with a column' => [
            'rules' => 'integer|exists:users,id',
            'expected' => [
                'type' => 'integer',
                'description' => 'must exist in users.id',
                'lookup' => ['table' => 'users', 'column' => 'id'],
            ],
        ];

        yield 'exists without a column uses the field name' => [
            'rules' => 'exists:users',
            'expected' => [
                'type' => 'string',
                'description' => 'must exist in users.field',
                'lookup' => ['table' => 'users', 'column' => 'field'],
            ],
        ];

        yield 'exists with a model class has no lookup' => [
            'rules' => 'exists:App\Models\User,id',
            'expected' => ['type' => 'string', 'description' => 'must exist in App\Models\User.id'],
        ];

        yield 'unique has a hint and no lookup' => [
            'rules' => 'unique:users,email,5',
            'expected' => ['type' => 'string', 'description' => 'must be unique in users.email'],
        ];

        yield 'prohibited is left out of autofill' => [
            'rules' => 'prohibited',
            'expected' => ['type' => 'string', 'description' => 'prohibited', 'skipAutofill' => true],
        ];

        yield 'exclude if is left out of autofill' => [
            'rules' => 'exclude_if:role,admin',
            'expected' => ['type' => 'string', 'description' => 'exclude if role is admin', 'skipAutofill' => true],
        ];

        yield 'unknown rule changes nothing' => [
            'rules' => 'string|some_custom_rule:1',
            'expected' => ['type' => 'string'],
        ];
    }

    /**
     * @param  string[]  $expectedRequired
     */
    #[DataProvider('requirementProvider')]
    public function test_it_marks_conditional_fields_as_not_required(string $rules, array $expectedRequired, ?string $expectedLabel): void
    {
        // Act

        $schema = $this->buildSchema(['field' => $rules]);

        // Assert

        $this->assertSame($expectedRequired, $schema['required']);
        $this->assertSame($expectedLabel, $schema['properties']['field']['description'] ?? null);
    }

    public static function requirementProvider(): Generator
    {
        yield 'required' => ['required|string', ['field'], null];

        yield 'sometimes' => ['sometimes|string', [], null];

        yield 'sometimes with required' => ['sometimes|required|string', [], null];

        yield 'required if' => ['required_if:role,admin|string', [], 'required if role is admin'];

        yield 'required unless' => ['required_unless:role,admin|string', [], 'required unless role is admin'];

        yield 'required with' => ['required_with:a,b|string', [], 'required with a or b'];

        yield 'required without' => ['required_without:a|string', [], 'required without a'];

        yield 'required with all' => ['required_with_all:a,b|string', [], 'required with all of a, b'];

        yield 'required without all' => ['required_without_all:a,b|string', [], 'required without all of a, b'];

        yield 'plain required stays required next to a condition' => ['required|required_with:a|string', ['field'], 'required with a'];
    }

    #[DataProvider('ruleOrderProvider')]
    public function test_it_reads_min_and_max_by_type_whatever_the_rule_order(string $rules, array $expected): void
    {
        // Act

        $property = $this->buildSchema(['field' => $rules])['properties']['field'];

        // Assert

        $this->assertEquals($expected, $property);
    }

    public static function ruleOrderProvider(): Generator
    {
        yield 'min before string' => ['min:3|string', ['type' => 'string', 'minLength' => 3]];

        yield 'max before string' => ['max:9|string', ['type' => 'string', 'maxLength' => 9]];

        yield 'min before integer' => ['min:3|integer', ['type' => 'integer', 'minimum' => 3]];

        yield 'max and min before numeric' => ['max:9|min:3|numeric', ['type' => 'number', 'minimum' => 3.0, 'maximum' => 9.0]];

        yield 'size before array' => ['size:2|array', ['type' => 'array', 'minItems' => 2, 'maxItems' => 2]];

        yield 'min after integer' => ['integer|min:3', ['type' => 'integer', 'minimum' => 3]];
    }

    public function test_it_adds_the_confirmation_sibling_for_a_confirmed_field(): void
    {
        // Act

        $schema = $this->buildSchema(['password' => 'required|string|confirmed']);

        // Assert

        $this->assertSame(['password', 'password_confirmation'], array_keys($schema['properties']));
        $this->assertSame(['password', 'password_confirmation'], $schema['required']);
    }

    public function test_it_keeps_an_explicit_confirmation_rule(): void
    {
        // Act

        $schema = $this->buildSchema([
            'password' => 'required|string|confirmed',
            'password_confirmation' => 'nullable|string|min:8',
        ]);

        // Assert

        $this->assertSame(['type' => ['string', 'null'], 'minLength' => 8], $schema['properties']['password_confirmation']);
    }

    public function test_it_reads_distinct_from_the_wildcard_rules(): void
    {
        // Act

        $schema = $this->buildSchema(['tags' => 'required|array', 'tags.*' => 'string|distinct']);

        // Assert

        $this->assertTrue($schema['properties']['tags']['uniqueItems']);
    }

    public function test_it_keeps_nullable_and_hints_on_a_property_that_gets_children(): void
    {
        // Act

        $schema = $this->buildSchema([
            'address' => 'nullable|array|required_if:type,home',
            'address.*.city' => 'string',
            'profile' => 'nullable',
            'profile.name' => 'string',
        ]);

        // Assert

        $this->assertSame(['array', 'null'], $schema['properties']['address']['type']);
        $this->assertSame('required if type is home', $schema['properties']['address']['description']);
        $this->assertSame(['object', 'null'], $schema['properties']['profile']['type']);
    }

    public function test_it_maps_rule_objects(): void
    {
        // Act

        $schema = $this->buildSchema([
            'a' => ['required', Rule::in(['x', 'y'])],
            'b' => [Rule::requiredIf(true)],
            'c' => [Rule::requiredIf(false)],
            'd' => [Rule::prohibitedIf(true)],
            'e' => [Rule::when(false, ['required'], ['nullable', 'string'])],
            'f' => [Rule::requiredIf(fn (): bool => true)],
            'g' => [Rule::exists('users', 'id')],
            'h' => [Rule::unique('users')],
            'i' => [Rule::dimensions()->minWidth(10)],
            'j' => [fn (): bool => true],
        ]);

        // Assert

        $properties = $schema['properties'];

        $this->assertSame(['x', 'y'], $properties['a']['enum']);
        $this->assertContains('b', $schema['required']);
        $this->assertNotContains('c', $schema['required']);
        $this->assertTrue($properties['d']['skipAutofill']);
        $this->assertSame(['string', 'null'], $properties['e']['type']);
        $this->assertStringContainsString('required if a closure passes', $properties['f']['description']);
        $this->assertSame(['table' => 'users', 'column' => 'id'], $properties['g']['lookup']);
        $this->assertArrayNotHasKey('lookup', $properties['h']);
        $this->assertSame('binary', $properties['i']['format']);
        $this->assertStringContainsString('custom closure rule', $properties['j']['description']);
    }

    /**
    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function buildSchema(array $rules): array
    {
        $builder = new SchemaBuilder(
            new PropertyBuilder(new RuleToSchemaMapper),
            new BinaryContentInferrer,
        );

        return $builder->buildSchemaFromRuleset(Ruleset::fromLaravelRules($rules))->toJsonSchema();
    }
}
