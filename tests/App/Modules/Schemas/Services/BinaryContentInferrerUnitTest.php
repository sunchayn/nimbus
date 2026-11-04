<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\Services\BinaryContentInferrer;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(BinaryContentInferrer::class)]
class BinaryContentInferrerUnitTest extends TestCase
{
    /**
     * @param  SchemaPropertyInterface[]  $properties
     * @param  string[]  $expectedEncoded
     */
    #[DataProvider('inferDataProvider')]
    public function test_it_infers_base64_content_from_siblings(array $properties, array $expectedEncoded): void
    {
        // Arrange

        $inferrer = new BinaryContentInferrer;

        // Act

        $result = $inferrer->infer($properties);

        // Assert

        $encoded = collect($result)
            ->filter(fn (SchemaPropertyInterface $property): bool => ($property->toJsonSchema()['contentEncoding'] ?? null) === 'base64')
            ->map(fn (SchemaPropertyInterface $property): string => $property->getName())
            ->values()
            ->all();

        $this->assertSame($expectedEncoded, $encoded);
        $this->assertCount(count($properties), $result);
    }

    public static function inferDataProvider(): Generator
    {
        yield 'content next to a mime type' => [
            'properties' => [
                new StringSchemaProperty(name: 'name'),
                new StringSchemaProperty(name: 'content'),
                new StringSchemaProperty(name: 'mime_type'),
            ],
            'expectedEncoded' => ['content'],
        ];

        yield 'every content name is recognized' => [
            'properties' => [
                new StringSchemaProperty(name: 'contents'),
                new StringSchemaProperty(name: 'data'),
                new StringSchemaProperty(name: 'body'),
                new StringSchemaProperty(name: 'blob'),
                new StringSchemaProperty(name: 'bytes'),
                new StringSchemaProperty(name: 'payload'),
                new StringSchemaProperty(name: 'file'),
                new StringSchemaProperty(name: 'content_type'),
            ],
            'expectedEncoded' => ['contents', 'data', 'body', 'blob', 'bytes', 'payload', 'file'],
        ];

        yield 'camel case names are recognized' => [
            'properties' => [
                new StringSchemaProperty(name: 'content'),
                new StringSchemaProperty(name: 'mimeType'),
            ],
            'expectedEncoded' => ['content'],
        ];

        yield 'content next to a media type' => [
            'properties' => [
                new StringSchemaProperty(name: 'data'),
                new StringSchemaProperty(name: 'media_type'),
            ],
            'expectedEncoded' => ['data'],
        ];

        yield 'content next to a file name' => [
            'properties' => [
                new StringSchemaProperty(name: 'content'),
                new StringSchemaProperty(name: 'filename'),
            ],
            'expectedEncoded' => ['content'],
        ];

        yield 'content next to a file name in snake case' => [
            'properties' => [
                new StringSchemaProperty(name: 'content'),
                new StringSchemaProperty(name: 'file_name'),
            ],
            'expectedEncoded' => ['content'],
        ];

        yield 'content without a file companion' => [
            'properties' => [
                new StringSchemaProperty(name: 'title'),
                new StringSchemaProperty(name: 'content'),
            ],
            'expectedEncoded' => [],
        ];

        yield 'a plain name is not a file companion' => [
            'properties' => [
                new StringSchemaProperty(name: 'name'),
                new StringSchemaProperty(name: 'content'),
            ],
            'expectedEncoded' => [],
        ];

        yield 'content with a format' => [
            'properties' => [
                new StringSchemaProperty(name: 'content', stringFormat: StringFormat::EMAIL),
                new StringSchemaProperty(name: 'mime_type'),
            ],
            'expectedEncoded' => [],
        ];

        yield 'content with an enum' => [
            'properties' => [
                new StringSchemaProperty(name: 'content', enum: ['a', 'b']),
                new StringSchemaProperty(name: 'mime_type'),
            ],
            'expectedEncoded' => [],
        ];

        yield 'content that is not a string' => [
            'properties' => [
                new IntegerSchemaProperty(name: 'content'),
                new StringSchemaProperty(name: 'mime_type'),
            ],
            'expectedEncoded' => [],
        ];

        yield 'the companion is never marked' => [
            'properties' => [
                new StringSchemaProperty(name: 'filename'),
                new StringSchemaProperty(name: 'mime_type'),
            ],
            'expectedEncoded' => [],
        ];

        yield 'no properties' => [
            'properties' => [],
            'expectedEncoded' => [],
        ];
    }
}
