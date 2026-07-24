<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Converts a concrete array into a Schema Value Object.
 *
 * @final
 */
class InferSchemaFromArrayAction
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function execute(?array $payload): Schema
    {
        if ($payload === null) {
            return Schema::empty();
        }

        $map = [];

        foreach ($payload as $key => $value) {
            $map[$key] = $this->inferProperty((string) $key, $value);
        }

        return Schema::fromArrayMap($map);
    }

    private function inferProperty(string $name, mixed $value): SchemaPropertyInterface
    {
        if ($value === null) {
            return new StringSchemaProperty($name, required: true, nullable: true);
        }

        if (is_bool($value)) {
            return new BooleanSchemaProperty($name, required: true);
        }

        if (is_int($value)) {
            return new IntegerSchemaProperty($name, required: true);
        }

        if (is_float($value)) {
            return new NumberSchemaProperty($name, required: true);
        }

        if (is_array($value)) {
            if (! array_is_list($value)) {
                return new ObjectSchemaProperty(
                    name: $name,
                    required: true,
                    schema: $this->execute($value),
                );
            }

            $itemProperty = isset($value[0])
                ? $this->inferProperty($name.'_item', $value[0])
                : new StringSchemaProperty($name.'_item', required: true);

            return new ArraySchemaProperty(
                name: $name,
                required: true,
                schemaProperty: $itemProperty,
            );
        }

        return new StringSchemaProperty($name, required: true);
    }
}
