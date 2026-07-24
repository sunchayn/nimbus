<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromArrayAction;

#[CoversClass(InferSchemaFromArrayAction::class)]
class InferSchemaFromArrayActionUnitTest extends TestCase
{
    public function test_it_transforms_null_to_empty_schema(): void
    {
        // Arrange

        $action = new InferSchemaFromArrayAction;

        // Act & Assert

        $this->assertTrue($action->execute(null)->isEmpty());
    }

    public function test_it_transforms_concrete_payload_to_schema(): void
    {
        // Arrange

        $action = new InferSchemaFromArrayAction;

        // Act

        $schema = $action->execute(
            [
                'id' => 123,
                'name' => 'John',
                'is_active' => true,
                'score' => 4.5,
                'tags' => ['admin', 'user'],
                'empty_list' => [],
                'profile' => [
                    'bio' => 'Developer',
                ],
                'unknown' => null,
            ],
        );

        // Assert

        $this->assertEquals(
            [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                    ],
                    'name' => [
                        'type' => 'string',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                    ],
                    'score' => [
                        'type' => 'number',
                    ],
                    'tags' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'empty_list' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'profile' => [
                        'type' => 'object',
                        'properties' => [
                            'bio' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'bio',
                        ],
                        'additionalProperties' => false,
                    ],
                    'unknown' => [
                        'type' => ['string', 'null'],
                    ],
                ],
                'required' => [
                    'id',
                    'name',
                    'is_active',
                    'score',
                    'tags',
                    'empty_list',
                    'profile',
                    'unknown',
                ],
                'additionalProperties' => false,
            ],
            $schema->toArray(),
        );
    }
}
