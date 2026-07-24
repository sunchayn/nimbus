<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use Generator;
use PhpParser\Node\Expr\Assign;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetPhpTypeFromAstScalarAction;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ArrayAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ObjectAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ScalarAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromArrayAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromArrayAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAstAction;

#[CoversClass(InferSchemaFromArrayAstAction::class)]
class InferSchemaFromArrayAstActionUnitTest extends TestCase
{
    #[DataProvider('astArraySchemaProvider')]
    public function test_infers_schema_from_array_ast(
        string $phpCode,
        ?VariablesContext $context,
        array $expectedSchemaArray
    ): void {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse($phpCode) ?? [];

        $assignNode = (new NodeFinder)->findFirstInstanceOf($stmts, Assign::class);

        $spatieAstAction = new InferSchemaFromSpatieDataObjectAstAction(
            inferSchemaFromSpatieDataObjectAction: $this->createMock(InferSchemaFromSpatieDataObjectAction::class),
        );

        $action = new InferSchemaFromArrayAstAction(
            inferSchemaFromSpatieDataObjectAstAction: $spatieAstAction,
            getPhpTypeFromAstScalarAction: new GetPhpTypeFromAstScalarAction,
            inferSchemaFromArrayAction: new InferSchemaFromArrayAction,
        );

        // Act

        $schema = $action->execute($assignNode->expr, context: $context);

        // Assert

        $this->assertEquals($expectedSchemaArray, $schema->toArray());
    }

    public static function astArraySchemaProvider(): Generator
    {
        yield 'literal array with scalars and nested array' => [
            'phpCode' => <<<'PHP'
                <?php
                $x = [
                    'name' => 'Alice',
                    'age' => 30,
                    'address' => [
                        'city' => 'NY',
                    ],
                ];
                PHP,
            'context' => null,
            'expectedSchemaArray' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                    'age' => [
                        'type' => 'integer',
                    ],
                    'address' => [
                        'type' => 'object',
                        'properties' => [
                            'city' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'city',
                        ],
                        'additionalProperties' => false,
                    ],
                ],
                'required' => [
                    'name',
                    'age',
                    'address',
                ],
                'additionalProperties' => false,
            ],
        ];

        yield 'array with unsupported non-string keys and variables' => [
            'phpCode' => '<?php $x = ["Alice", 123 => "Bob", "var" => $myVar];',
            'context' => null,
            'expectedSchemaArray' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'var' => [
                        'type' => 'string',
                    ],
                ],
                'required' => [
                    'var',
                ],
                'additionalProperties' => false,
            ],
        ];

        yield 'array resolving variables from context' => [
            'phpCode' => <<<'PHP'
                <?php
                $x = [
                    'arr' => $arrVar,
                    'obj' => $objVar,
                    'intVal' => $intVar,
                    'floatVal' => $floatVar,
                    'boolVal' => $boolVar,
                    'strVal' => $strVar,
                ];
                PHP,
            'context' => new VariablesContext([
                new ArrayAstContextValue(['nested' => 'val'], variableName: 'arrVar'),
                new ObjectAstContextValue(stdClass::class, variableName: 'objVar'),
                new ScalarAstContextValue(42, variableName: 'intVar'),
                new ScalarAstContextValue(3.14, variableName: 'floatVar'),
                new ScalarAstContextValue(true, variableName: 'boolVar'),
                new ScalarAstContextValue('hello', variableName: 'strVar'),
            ]),
            'expectedSchemaArray' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'arr' => [
                        'type' => 'object',
                        'properties' => [
                            'nested' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'nested',
                        ],
                        'additionalProperties' => false,
                    ],
                    'obj' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                    ],
                    'intVal' => [
                        'type' => 'integer',
                    ],
                    'floatVal' => [
                        'type' => 'number',
                    ],
                    'boolVal' => [
                        'type' => 'boolean',
                    ],
                    'strVal' => [
                        'type' => 'string',
                    ],
                ],
                'required' => [
                    'arr',
                    'obj',
                    'intVal',
                    'floatVal',
                    'boolVal',
                    'strVal',
                ],
                'additionalProperties' => false,
            ],
        ];

        yield 'array with list array elements' => [
            'phpCode' => <<<'PHP'
                <?php
                $x = [
                    'tags' => ['api', 'nimbus', 'testing'],
                    'scores' => [10, 20, 30],
                ];
                PHP,
            'context' => null,
            'expectedSchemaArray' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'tags' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'scores' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
                'required' => [
                    'tags',
                    'scores',
                ],
                'additionalProperties' => false,
            ],
        ];
    }
}
