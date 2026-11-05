<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Services;

use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Marks string properties that look like base64 file content.
 *
 * A property such as "content" is only binary when its siblings describe a file,
 * for example { name, content, mime_type }. A plain { title, content } stays text.
 */
class BinaryContentInferrer
{
    private const CONTENT_NAMES = ['content', 'contents', 'data', 'body', 'blob', 'bytes', 'payload', 'file'];

    private const FILE_COMPANION_NAMES = ['mime_type', 'content_type', 'media_type', 'filename', 'file_name'];

    /**
     * @param  SchemaPropertyInterface[]  $properties  The direct properties of one object.
     * @return SchemaPropertyInterface[]
     */
    public function infer(array $properties): array
    {
        if (! $this->hasFileCompanion($properties)) {
            return $properties;
        }

        return array_map(
            fn (SchemaPropertyInterface $property): SchemaPropertyInterface => $this->isContentProperty($property)
                ? $property->withContentEncoding(StringSchemaProperty::CONTENT_ENCODING_BASE64)
                : $property,
            $properties,
        );
    }

    /**
     * @phpstan-assert-if-true StringSchemaProperty $property
     */
    private function isContentProperty(SchemaPropertyInterface $property): bool
    {
        return $property instanceof StringSchemaProperty
            && $property->isUnconstrainedText()
            && in_array(Str::snake($property->getName()), self::CONTENT_NAMES, true);
    }

    /**
     * @param  SchemaPropertyInterface[]  $properties
     */
    private function hasFileCompanion(array $properties): bool
    {
        foreach ($properties as $property) {
            if (in_array(Str::snake($property->getName()), self::FILE_COMPANION_NAMES, true)) {
                return true;
            }
        }

        return false;
    }
}
