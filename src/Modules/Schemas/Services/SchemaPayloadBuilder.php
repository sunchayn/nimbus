<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Services;

use Illuminate\Support\Arr;
use stdClass;

/**
 * Builds a sample payload from a JSON schema, without calling the application.
 */
class SchemaPayloadBuilder
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $overrides
     * @param  array<string, array<int, int|string|float>>  $lookupValues  Existing values by `table.column`, for the exists rules.
     * @return array{payload: array<string, mixed>, notes: list<string>}
     */
    public function build(array $schema, string $mode, array $overrides = [], array $lookupValues = []): array
    {
        $schema = $this->withLookupValues(schema: $schema, lookupValues: $lookupValues);

        $built = $this->object(
            schema: $schema,
            onlyRequired: $mode === 'minimal',
            path: '',
        );

        $payload = $built['value'];
        $notes = $built['notes'];

        foreach ($overrides as $key => $value) {
            Arr::set($payload, $key, $value);
        }

        if ($mode === 'invalid') {
            $broken = $this->breakPayload(schema: $schema, payload: $payload);

            $payload = $broken['payload'];
            $notes[] = $broken['note'];
        }

        return [
            'payload' => $payload,
            'notes' => $notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{value: array<string, mixed>, notes: list<string>}
     */
    private function object(array $schema, bool $onlyRequired, string $path): array
    {
        $required = $this->listRequired(schema: $schema);
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];

        $payload = [];
        $notes = [];

        foreach ($properties as $name => $property) {
            if ($onlyRequired && ! in_array($name, $required, true)) {
                continue;
            }

            if (! is_array($property)) {
                continue;
            }

            $built = $this->value(
                schema: $property,
                onlyRequired: $onlyRequired,
                path: $this->joinPath(path: $path, segment: (string) $name),
            );

            $payload[$name] = $built['value'];
            $notes = [...$notes, ...$built['notes']];
        }

        return [
            'value' => $payload,
            'notes' => $notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{value: mixed, notes: list<string>}
     */
    private function value(array $schema, bool $onlyRequired, string $path): array
    {
        if (array_key_exists('const', $schema)) {
            return ['value' => $schema['const'], 'notes' => []];
        }

        if (is_array($schema['enum'] ?? null) && $schema['enum'] !== []) {
            return ['value' => $schema['enum'][0], 'notes' => []];
        }

        $built = $this->buildTypedValue(schema: $schema, onlyRequired: $onlyRequired, path: $path);

        return [
            'value' => $built['value'],
            'notes' => [...$built['notes'], ...$this->listExistsNotes(schema: $schema, path: $path)],
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{value: mixed, notes: list<string>}
     */
    private function buildTypedValue(array $schema, bool $onlyRequired, string $path): array
    {
        return match ($this->findType(schema: $schema)) {
            'object' => $this->buildObjectValue($schema, $onlyRequired, $path),
            'array' => $this->array($schema, $onlyRequired, $path),
            'integer' => ['value' => $this->number($schema, integer: true), 'notes' => []],
            'number' => ['value' => $this->number($schema, integer: false), 'notes' => []],
            'boolean' => ['value' => true, 'notes' => []],
            'string' => ['value' => $this->string($schema), 'notes' => $this->listStringNotes($schema, $path)],
            default => ['value' => null, 'notes' => []],
        };
    }

    /**
     * An object without properties is sent as an empty JSON object and not as an empty list.
     *
     * @param  array<string, mixed>  $schema
     * @return array{value: mixed, notes: list<string>}
     */
    private function buildObjectValue(array $schema, bool $onlyRequired, string $path): array
    {
        $built = $this->object($schema, $onlyRequired, $path);

        return [
            'value' => $built['value'] ?: new stdClass,
            'notes' => $built['notes'],
        ];
    }

    /**
     * A property with an exists rule gets the first existing value that fits its type.
     * The value is set as the only allowed one, so the rest of the builder uses it as it is.
     *
     * @param  array<array-key, mixed>  $schema
     * @param  array<string, array<int, int|string|float>>  $lookupValues
     * @return array<array-key, mixed>
     */
    private function withLookupValues(array $schema, array $lookupValues): array
    {
        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $schema[$key] = $this->withLookupValues(schema: $value, lookupValues: $lookupValues);
            }
        }

        $lookup = $schema['lookup'] ?? null;

        if (! is_array($lookup) || ! isset($lookup['table'], $lookup['column'])) {
            return $schema;
        }

        $values = $lookupValues["{$lookup['table']}.{$lookup['column']}"] ?? [];

        $value = $this->findValueOfType(type: $this->findType(schema: $schema), values: $values);

        if ($value === null) {
            return $schema;
        }

        $schema['enum'] = [$value];

        return $schema;
    }

    /**
     * @param  array<int, int|string|float>  $values
     */
    private function findValueOfType(?string $type, array $values): int|string|float|null
    {
        foreach ($values as $value) {
            if ($type === 'integer' && is_numeric($value) && (int) $value == $value) {
                return (int) $value;
            }

            if ($type === 'number' && is_numeric($value)) {
                return $value + 0;
            }

            if ($type === 'string') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * An exists rule that got no real value tells the agent how to set one.
     *
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    private function listExistsNotes(array $schema, string $path): array
    {
        $lookup = $schema['lookup'] ?? null;

        if (is_array($lookup) && isset($lookup['table'], $lookup['column'])) {
            return [sprintf(
                '`%s` must exist in %s.%s, and no value could be read from it. The value is a placeholder, so set it with `overrides`.',
                $path,
                $lookup['table'],
                $lookup['column'],
            )];
        }

        $description = (string) ($schema['description'] ?? '');

        if (preg_match('/must exist in ([^.]+\.\w+)/', $description, $matches) !== 1) {
            return [];
        }

        return [sprintf(
            '`%s` must exist in %s, and Nimbus cannot read it from the application. The value is a placeholder, so set it with `overrides`.',
            $path,
            $matches[1],
        )];
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function findType(array $schema): ?string
    {
        $type = $schema['type'] ?? null;

        if (is_array($type)) {
            $type = Arr::first($type, fn (mixed $candidate): bool => $candidate !== 'null');
        }

        return is_string($type) ? $type : null;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{value: list<mixed>, notes: list<string>}
     */
    private function array(array $schema, bool $onlyRequired, string $path): array
    {
        $items = is_array($schema['items'] ?? null) ? $schema['items'] : [];
        $count = max(1, (int) ($schema['minItems'] ?? 1));

        $values = [];
        $notes = [];

        for ($index = 0; $index < $count; $index++) {
            $built = $this->value(
                schema: $items,
                onlyRequired: $onlyRequired,
                path: $this->joinPath(path: $path, segment: (string) $index),
            );

            $values[] = $built['value'];
            $notes = [...$notes, ...$built['notes']];
        }

        return [
            'value' => $values,
            'notes' => $notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function number(array $schema, bool $integer): int|float
    {
        $minimum = $schema['minimum'] ?? $this->getExclusiveMinimumFloor(schema: $schema);
        $maximum = $schema['maximum'] ?? null;

        $value = is_numeric($minimum) ? $minimum : 1;

        if (is_numeric($maximum) && $value > $maximum) {
            $value = $maximum;
        }

        return $integer ? (int) $value : (float) $value;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function getExclusiveMinimumFloor(array $schema): int|float|null
    {
        if (! isset($schema['exclusiveMinimum'])) {
            return null;
        }

        return $schema['exclusiveMinimum'] + 1;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function string(array $schema): string
    {
        $format = $schema['format'] ?? null;

        $value = match ($format) {
            'email' => 'user@example.com',
            'uuid' => '123e4567-e89b-42d3-a456-426614174000',
            'uri', 'url' => 'https://example.com',
            'date' => '2026-01-01',
            'date-time' => '2026-01-01T00:00:00Z',
            'ipv4' => '127.0.0.1',
            'binary' => '',
            default => 'example',
        };

        // A format fixes the shape of the value, so the length limits only apply without one.
        if ($format !== null) {
            return $value;
        }

        $value = str_pad($value, (int) ($schema['minLength'] ?? 0), 'a');

        if (! isset($schema['maxLength'])) {
            return $value;
        }

        return substr($value, 0, (int) $schema['maxLength']);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    private function listStringNotes(array $schema, string $path): array
    {
        $notes = [];

        if (($schema['format'] ?? null) === 'binary') {
            $notes[] = "`{$path}` is a file field. Upload it from the UI, an agent cannot send files yet.";
        }

        if (isset($schema['pattern'])) {
            $notes[] = "`{$path}` has a pattern the builder cannot satisfy. Set it with overrides.";
        }

        return $notes;
    }

    /**
     * Makes the payload fail one rule and says which.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $payload
     * @return array{payload: array<string, mixed>, note: string}
     */
    private function breakPayload(array $schema, array $payload): array
    {
        $leftOut = Arr::first(
            $this->listRequired(schema: $schema),
            static fn (mixed $name): bool => array_key_exists($name, $payload),
        );

        if ($leftOut !== null) {
            unset($payload[$leftOut]);

            return [
                'payload' => $payload,
                'note' => "The required field `{$leftOut}` was left out.",
            ];
        }

        $first = array_key_first($payload);

        if ($first !== null) {
            $payload[$first] = ['unexpected' => true];

            return [
                'payload' => $payload,
                'note' => "The field `{$first}` has the wrong type.",
            ];
        }

        $payload['unknown_field'] = true;

        return [
            'payload' => $payload,
            'note' => 'An unknown field was added.',
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, mixed>
     */
    private function listRequired(array $schema): array
    {
        return is_array($schema['required'] ?? null) ? $schema['required'] : [];
    }

    private function joinPath(string $path, string $segment): string
    {
        return $path === '' ? $segment : "{$path}.{$segment}";
    }
}
