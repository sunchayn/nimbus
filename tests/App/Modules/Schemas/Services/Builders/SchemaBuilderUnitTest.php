<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services\Builders;

use Generator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\In;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\Services\BinaryContentInferrer;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\PropertyBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\EnumRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\InRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\RuleToSchemaMapper;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\FieldPath;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\PropertyAnnotations;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services\Builders\Stubs\Base64RuleStub;
use Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services\Builders\Stubs\StatusEnumStub;

#[CoversClass(SchemaBuilder::class)]
#[CoversClass(PropertyBuilder::class)]
#[CoversClass(FieldPath::class)]
#[CoversClass(RuleToSchemaMapper::class)]
#[CoversClass(InRuleProcessor::class)]
#[CoversClass(EnumRuleProcessor::class)]
// TODO [Test] Move the mapper and process to their own tests.
class SchemaBuilderUnitTest extends TestCase
{
    private SchemaBuilder $schemaBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaBuilder = $this->createSchemaBuilder();
    }

    #[DataProvider('schemaBuilderDataProvider')]
    public function test_builds_schema_with_correct_properties(
        array $rules,
        Schema $expectedSchema,
    ): void {
        // Act

        $actual = $this->schemaBuilder->buildSchemaFromRuleset(Ruleset::fromLaravelRules($rules));

        // Assert

        $this->assertEquals(
            $expectedSchema,
            $actual,
        );
    }

    public static function schemaBuilderDataProvider(): Generator
    {
        yield 'simple rules' => [
            'rules' => [
                'name' => 'required|string',
                'email' => 'required|email',
                'age' => 'required_with:email|integer|min:18|max:99',
                'statuses' => ['required', new Enum(StatusEnumStub::class)],
                'statuses_v2' => ['required', Rule::enum(StatusEnumStub::class)],
                'role' => ['required', new In(1, 2, 3, 4)],
                'role_v2' => ['required', Rule::in(1, 2, 3, 4)],
                'role_2' => ['required', new In(new stdClass, 2, 3)], // <- Assert we rebase the array after filtering.
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new StringSchemaProperty(name: 'name', required: true),
                    new StringSchemaProperty(name: 'email', required: true, stringFormat: StringFormat::EMAIL),
                    new IntegerSchemaProperty(name: 'age', required: false, minimum: 18, maximum: 99, propertyAnnotations: new PropertyAnnotations(hints: ['required with email'])),
                    new StringSchemaProperty(name: 'statuses', required: true, enum: ['inactive', 'active']),
                    new StringSchemaProperty(name: 'statuses_v2', required: true, enum: ['inactive', 'active']),
                    new IntegerSchemaProperty(name: 'role', required: true, enum: [1, 2, 3, 4]),
                    new IntegerSchemaProperty(name: 'role_v2', required: true, enum: [1, 2, 3, 4]),
                    new IntegerSchemaProperty(name: 'role_2', required: true, enum: [2, 3]),
                ],
                extractionError: null,
            ),
        ];

        yield 'nested object rules' => [
            'rules' => [
                'user.name' => 'required|string',
                'user.email' => 'required|email',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ObjectSchemaProperty(
                        name: 'user',
                        required: false,
                        schema: new Schema(
                            properties: [
                                new StringSchemaProperty(name: 'name', required: true),
                                new StringSchemaProperty(name: 'email', required: true, stringFormat: StringFormat::EMAIL),
                            ],
                            extractionError: null,
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'array of primitives' => [
            'rules' => [
                'tags' => 'array',
                'tags.*' => 'string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ArraySchemaProperty(
                        name: 'tags',
                        required: false,
                        schemaProperty: new StringSchemaProperty(
                            name: 'tag', // <- Singular value of parent property `tags`.
                            required: false,
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'array size rules declared before item rules' => [
            'rules' => [
                'files' => 'required|array|min:1|max:3',
                'files.*.name' => 'string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ArraySchemaProperty(
                        name: 'files',
                        required: true,
                        schemaProperty: new ObjectSchemaProperty(
                            name: 'item',
                            required: false,
                            schema: new Schema(
                                properties: [
                                    new StringSchemaProperty(name: 'name', required: false),
                                ],
                                extractionError: null,
                            )
                        ),
                        minItems: 1,
                        maxItems: 3,
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'array size rules declared after item rules' => [
            'rules' => [
                'files.*.name' => 'string',
                'files' => 'required|array|min:1|max:3',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ArraySchemaProperty(
                        name: 'files',
                        required: true,
                        schemaProperty: new ObjectSchemaProperty(
                            name: 'item',
                            required: false,
                            schema: new Schema(
                                properties: [
                                    new StringSchemaProperty(name: 'name', required: false),
                                ],
                                extractionError: null,
                            )
                        ),
                        minItems: 1,
                        maxItems: 3,
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'array of primitives keeps size rules' => [
            'rules' => [
                'attachments' => 'sometimes|array|max:3',
                'attachments.*' => 'string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ArraySchemaProperty(
                        name: 'attachments',
                        required: false,
                        schemaProperty: new StringSchemaProperty(name: 'attachment', required: false),
                        maxItems: 3,
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'array of objects rules' => [
            'rules' => [
                'persons' => 'array|required',
                'persons.*.email' => 'string|email',
                'persons.*.username' => 'string|max:20',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ArraySchemaProperty(
                        name: 'persons',
                        required: true,
                        schemaProperty: new ObjectSchemaProperty(
                            name: 'item',
                            required: false,
                            schema: new Schema(
                                properties: [
                                    new StringSchemaProperty(
                                        name: 'email',
                                        required: false,
                                        stringFormat: StringFormat::EMAIL,
                                    ),
                                    new StringSchemaProperty(
                                        name: 'username',
                                        required: false,
                                        maxLength: 20,
                                    ),
                                ],
                                extractionError: null,
                            )
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'mixed data types' => [
            'rules' => [
                'id' => 'required|uuid',
                'name' => 'required|string',
                'age' => 'integer',
                'is_active' => 'boolean',
                'salary' => 'numeric',
                'config' => 'json',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new StringSchemaProperty(name: 'id', required: true, stringFormat: StringFormat::UUID),
                    new StringSchemaProperty(name: 'name', required: true),
                    new IntegerSchemaProperty(name: 'age', required: false),
                    new BooleanSchemaProperty(name: 'is_active', required: false),
                    new NumberSchemaProperty(name: 'salary', required: false),
                    new ObjectSchemaProperty(name: 'config', required: false, schema: new Schema([])),
                ],
                extractionError: null,
            ),
        ];

        yield 'base64 rule marks the string as base64 content' => [
            'rules' => [
                'photo' => 'required|string|base64',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new StringSchemaProperty(name: 'photo', required: true, contentEncoding: 'base64'),
                ],
                extractionError: null,
            ),
        ];

        yield 'rule class named base64 marks the string as base64 content' => [
            'rules' => [
                'photo' => ['required', new Base64RuleStub],
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new StringSchemaProperty(name: 'photo', required: true, contentEncoding: 'base64'),
                ],
                extractionError: null,
            ),
        ];

        yield 'base64 looking names mark the string as base64 content' => [
            'rules' => [
                'avatar_base64' => 'string',
                'logoB64' => 'string',
                'dataUri' => 'string',
                'base64' => 'string',
                'avatar' => 'string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new StringSchemaProperty(name: 'avatar_base64', contentEncoding: 'base64'),
                    new StringSchemaProperty(name: 'logoB64', contentEncoding: 'base64'),
                    new StringSchemaProperty(name: 'dataUri', contentEncoding: 'base64'),
                    new StringSchemaProperty(name: 'base64', contentEncoding: 'base64'),
                    new StringSchemaProperty(name: 'avatar'),
                ],
                extractionError: null,
            ),
        ];

        yield 'content next to a mime type is base64 content' => [
            'rules' => [
                'file.name' => 'required|string|max:255',
                'file.content' => 'required|string',
                'file.mime_type' => 'nullable|string|max:100',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ObjectSchemaProperty(
                        name: 'file',
                        required: false,
                        schema: new Schema(
                            properties: [
                                new StringSchemaProperty(name: 'name', required: true, maxLength: 255),
                                new StringSchemaProperty(name: 'content', required: true, contentEncoding: 'base64'),
                                new StringSchemaProperty(name: 'mime_type', required: false, nullable: true, maxLength: 100),
                            ],
                            extractionError: null,
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'content next to a mime type is base64 content in array items' => [
            'rules' => [
                'files' => 'required|array',
                'files.*.name' => 'required|string',
                'files.*.content' => 'required|string',
                'files.*.mime_type' => 'nullable|string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ArraySchemaProperty(
                        name: 'files',
                        required: true,
                        schemaProperty: new ObjectSchemaProperty(
                            name: 'item',
                            required: false,
                            schema: new Schema(
                                properties: [
                                    new StringSchemaProperty(name: 'name', required: true),
                                    new StringSchemaProperty(name: 'content', required: true, contentEncoding: 'base64'),
                                    new StringSchemaProperty(name: 'mime_type', required: false, nullable: true),
                                ],
                                extractionError: null,
                            ),
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'content next to a file name is base64 content at the root' => [
            'rules' => [
                'filename' => 'required|string',
                'data' => 'required|string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new StringSchemaProperty(name: 'filename', required: true),
                    new StringSchemaProperty(name: 'data', required: true, contentEncoding: 'base64'),
                ],
                extractionError: null,
            ),
        ];

        yield 'content without a file companion stays text' => [
            'rules' => [
                'post.title' => 'required|string',
                'post.content' => 'required|string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ObjectSchemaProperty(
                        name: 'post',
                        required: false,
                        schema: new Schema(
                            properties: [
                                new StringSchemaProperty(name: 'title', required: true),
                                new StringSchemaProperty(name: 'content', required: true),
                            ],
                            extractionError: null,
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'content with a format or an enum stays as is' => [
            'rules' => [
                'file.data' => 'string|email',
                'file.content' => ['string', Rule::in('a', 'b')],
                'file.mime_type' => 'string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ObjectSchemaProperty(
                        name: 'file',
                        required: false,
                        schema: new Schema(
                            properties: [
                                new StringSchemaProperty(name: 'data', stringFormat: StringFormat::EMAIL),
                                new StringSchemaProperty(name: 'content', enum: ['a', 'b']),
                                new StringSchemaProperty(name: 'mime_type'),
                            ],
                            extractionError: null,
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'empty rules' => [
            'rules' => [],
            'expectedSchema' => new Schema(
                properties: [],
                extractionError: null,
            ),
        ];

        yield 'deep nesting (object)' => [
            'rules' => [
                'company.department.team.member.name' => 'required|string',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ObjectSchemaProperty(
                        name: 'company',
                        required: false,
                        schema: new Schema(
                            properties: [
                                new ObjectSchemaProperty(
                                    name: 'department',
                                    required: false,
                                    schema: new Schema(
                                        properties: [
                                            new ObjectSchemaProperty(
                                                name: 'team',
                                                required: false,
                                                schema: new Schema(
                                                    properties: [
                                                        new ObjectSchemaProperty(
                                                            name: 'member',
                                                            required: false,
                                                            schema: new Schema(
                                                                properties: [
                                                                    new StringSchemaProperty(name: 'name', required: true),
                                                                ],
                                                                extractionError: null,
                                                            ),
                                                        ),
                                                    ],
                                                    extractionError: null,
                                                ),
                                            ),
                                        ],
                                        extractionError: null,
                                    ),
                                ),
                            ],
                            extractionError: null,
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];

        yield 'deep nesting (array)' => [
            'rules' => [
                'company.teams.*.members.*.member.name' => 'string',
                'company.teams.*.members' => 'required|array',
            ],
            'expectedSchema' => new Schema(
                properties: [
                    new ObjectSchemaProperty(
                        name: 'company',
                        required: false,
                        schema: new Schema(
                            properties: [
                                new ArraySchemaProperty(
                                    name: 'teams',
                                    required: false,
                                    schemaProperty: new ObjectSchemaProperty(
                                        name: 'item',
                                        required: false,
                                        schema: new Schema(
                                            properties: [
                                                new ArraySchemaProperty(
                                                    name: 'members',
                                                    required: true,
                                                    schemaProperty: new ObjectSchemaProperty(
                                                        name: 'item',
                                                        required: false,
                                                        schema: new Schema(
                                                            properties: [
                                                                new ObjectSchemaProperty(
                                                                    name: 'member',
                                                                    required: false,
                                                                    schema: new Schema(
                                                                        properties: [
                                                                            new StringSchemaProperty(
                                                                                name: 'name',
                                                                                required: false,
                                                                            ),
                                                                        ],
                                                                        extractionError: null,
                                                                    ),
                                                                ),
                                                            ],
                                                            extractionError: null,
                                                        ),
                                                    ),
                                                ),
                                            ],
                                            extractionError: null,
                                        ),
                                    ),
                                ),
                            ],
                            extractionError: null,
                        ),
                    ),
                ],
                extractionError: null,
            ),
        ];
    }

    #[DataProvider('formatDetectionDataProvider')]
    public function test_detects_formats_correctly(
        array $rules,
        string $propertyName,
        ?string $expectedFormat
    ): void {
        // Act

        $schema = $this->schemaBuilder->buildSchemaFromRuleset(Ruleset::fromLaravelRules($rules));

        // Assert

        $property = $this->findPropertyByName($schema, $propertyName);

        $this->assertNotNull($property, "Property '{$propertyName}' not found");

        // Access format via toJsonSchema() for compatibility with all property types
        $propertyArray = $property->toJsonSchema();
        $this->assertEquals($expectedFormat, $propertyArray['format'] ?? null);
    }

    public static function formatDetectionDataProvider(): Generator
    {
        yield 'email format' => [
            'rules' => ['email' => 'required|email'],
            'propertyName' => 'email',
            'expectedFormat' => 'email',
        ];

        yield 'UUID format' => [
            'rules' => ['id' => 'required|uuid'],
            'propertyName' => 'id',
            'expectedFormat' => 'uuid',
        ];

        yield 'date-time format' => [
            'rules' => ['created_at' => 'required|date'],
            'propertyName' => 'created_at',
            'expectedFormat' => 'date-time',
        ];

        yield 'file rule' => [
            'rules' => ['document' => 'required|file'],
            'propertyName' => 'document',
            'expectedFormat' => 'binary',
        ];

        yield 'image rule with parameters' => [
            'rules' => ['avatar' => 'required|mimes:jpg,png|max:2048'],
            'propertyName' => 'avatar',
            'expectedFormat' => 'binary',
        ];

        yield 'file rule object' => [
            'rules' => ['avatar' => ['required', File::image()->max(1024)]],
            'propertyName' => 'avatar',
            'expectedFormat' => 'binary',
        ];

        yield 'no format' => [
            'rules' => ['name' => 'required|string'],
            'propertyName' => 'name',
            'expectedFormat' => null,
        ];
    }

    #[DataProvider('enumValuesDataProvider')]
    public function test_extracts_enum_values_correctly(
        array $rules,
        string $propertyName,
        ?array $expectedEnum
    ): void {
        // Act

        $schema = $this->schemaBuilder->buildSchemaFromRuleset(Ruleset::fromLaravelRules($rules));

        // Assert

        $property = $this->findPropertyByName($schema, $propertyName);

        $this->assertNotNull($property, "Property '{$propertyName}' not found");

        // Access enum via toJsonSchema() for compatibility with all property types
        $propertyArray = $property->toJsonSchema();
        $this->assertEquals($expectedEnum, $propertyArray['enum'] ?? null);
    }

    public static function enumValuesDataProvider(): Generator
    {
        yield 'enum values' => [
            'rules' => ['status' => 'required|in:active,inactive,pending'],
            'propertyName' => 'status',
            'expectedEnum' => ['active', 'inactive', 'pending'],
        ];

        yield 'no enum' => [
            'rules' => ['name' => 'required|string'],
            'propertyName' => 'name',
            'expectedEnum' => null,
        ];
    }

    /*
     * Helpers.
     */

    private function findPropertyByName(Schema $schema, string $name): ?SchemaPropertyInterface
    {
        return Arr::first(
            $schema->properties,
            fn ($property) => $property->getName() === $name,
        );
    }

    /*
     * Generators.
     */

    private function createSchemaBuilder(): SchemaBuilder
    {
        $ruleMapper = new RuleToSchemaMapper;
        $propertyBuilder = new PropertyBuilder($ruleMapper);

        return new SchemaBuilder(
            $propertyBuilder,
            new BinaryContentInferrer,
        );
    }
}
