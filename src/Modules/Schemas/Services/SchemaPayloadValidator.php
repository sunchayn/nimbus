<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Services;

/**
 * Checks a payload against the JSON schemas Nimbus builds, without calling the application.
 * It covers the keywords the schema builders write. Other keywords are ignored.
 *
 * @phpstan-type SchemaError array{path: string, rule: string, message: string}
 */
class SchemaPayloadValidator
{
    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    public function validate(array $schema, mixed $payload): array
    {
        return $this->check(
            schema: $schema,
            value: $payload,
            path: '',
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    private function check(array $schema, mixed $value, string $path): array
    {
        if (isset($schema['type']) && ! $this->matchesType($schema['type'], $value)) {
            $allowedTypes = implode(' or ', (array) $schema['type']);

            return [$this->makeError($path, 'type', "Must be of type {$allowedTypes}.")];
        }

        return [
            ...$this->checkEnum($schema, $value, $path),
            ...$this->checkConst($schema, $value, $path),
            ...$this->checkByValueType($schema, $value, $path),
            ...$this->checkCombinations($schema, $value, $path),
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    private function checkEnum(array $schema, mixed $value, string $path): array
    {
        if (! is_array($schema['enum'] ?? null) || in_array($value, $schema['enum'], true)) {
            return [];
        }

        $allowedValues = collect($schema['enum'])->map(json_encode(...))->implode(', ');

        return [$this->makeError($path, 'enum', "Must be one of {$allowedValues}.")];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    private function checkConst(array $schema, mixed $value, string $path): array
    {
        if (! array_key_exists('const', $schema) || $value === $schema['const']) {
            return [];
        }

        return [$this->makeError($path, 'const', 'Must be '.json_encode($schema['const']).'.')];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    private function checkByValueType(array $schema, mixed $value, string $path): array
    {
        return match (true) {
            is_string($value) => $this->checkString($schema, $value, $path),
            is_int($value), is_float($value) => $this->checkNumber($schema, $value, $path),
            is_array($value) => $this->checkArray($schema, $value, $path),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    private function checkString(array $schema, string $value, string $path): array
    {
        $length = mb_strlen($value);

        $errors = [];

        if (isset($schema['minLength']) && $length < $schema['minLength']) {
            $errors[] = $this->makeError($path, 'minLength', sprintf('Must have at least %d characters.', $schema['minLength']));
        }

        if (isset($schema['maxLength']) && $length > $schema['maxLength']) {
            $errors[] = $this->makeError($path, 'maxLength', sprintf('Must have at most %d characters.', $schema['maxLength']));
        }

        if (isset($schema['pattern']) && ! $this->matchesPattern(pattern: (string) $schema['pattern'], value: $value)) {
            $errors[] = $this->makeError($path, 'pattern', "Must match the pattern {$schema['pattern']}.");
        }

        if (isset($schema['format']) && ! $this->matchesFormat((string) $schema['format'], $value)) {
            $errors[] = $this->makeError($path, 'format', sprintf('Must be a valid %s.', $schema['format']));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    private function checkNumber(array $schema, int|float $value, string $path): array
    {
        $errors = [];

        if (isset($schema['minimum']) && $value < $schema['minimum']) {
            $errors[] = $this->makeError($path, 'minimum', sprintf('Must be at least %s.', $schema['minimum']));
        }

        if (isset($schema['maximum']) && $value > $schema['maximum']) {
            $errors[] = $this->makeError($path, 'maximum', sprintf('Must be at most %s.', $schema['maximum']));
        }

        if (isset($schema['exclusiveMinimum']) && $value <= $schema['exclusiveMinimum']) {
            $errors[] = $this->makeError($path, 'exclusiveMinimum', sprintf('Must be greater than %s.', $schema['exclusiveMinimum']));
        }

        if (isset($schema['exclusiveMaximum']) && $value >= $schema['exclusiveMaximum']) {
            $errors[] = $this->makeError($path, 'exclusiveMaximum', sprintf('Must be less than %s.', $schema['exclusiveMaximum']));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<array-key, mixed>  $value
     * @return list<SchemaError>
     */
    private function checkArray(array $schema, array $value, string $path): array
    {
        if (array_is_list($value) && $value !== []) {
            return $this->checkList($schema, $value, $path);
        }

        return $this->checkObject($schema, $value, $path);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  list<mixed>  $value
     * @return list<SchemaError>
     */
    private function checkList(array $schema, array $value, string $path): array
    {
        $errors = [];

        if (isset($schema['minItems']) && count($value) < $schema['minItems']) {
            $errors[] = $this->makeError($path, 'minItems', sprintf('Must have at least %d items.', $schema['minItems']));
        }

        if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) {
            $errors[] = $this->makeError($path, 'maxItems', sprintf('Must have at most %d items.', $schema['maxItems']));
        }

        if (! is_array($schema['items'] ?? null)) {
            return $errors;
        }

        foreach ($value as $index => $item) {
            $errors = [...$errors, ...$this->check($schema['items'], $item, $this->join($path, (string) $index))];
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<array-key, mixed>  $value
     * @return list<SchemaError>
     */
    private function checkObject(array $schema, array $value, string $path): array
    {
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];

        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        $errors = [];

        foreach ($required as $name) {
            if (! array_key_exists($name, $value)) {
                $errors[] = $this->makeError($this->join($path, (string) $name), 'required', 'This field is required.');
            }
        }

        foreach ($value as $name => $item) {
            if (isset($properties[$name]) && is_array($properties[$name])) {
                $errors = [...$errors, ...$this->check($properties[$name], $item, $this->join($path, (string) $name))];
            } elseif (($schema['additionalProperties'] ?? true) === false) {
                $errors[] = $this->makeError($this->join($path, (string) $name), 'additionalProperties', 'This field is not allowed.');
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<SchemaError>
     */
    private function checkCombinations(array $schema, mixed $value, string $path): array
    {
        $allOf = is_array($schema['allOf'] ?? null) ? $schema['allOf'] : [];

        $errors = [];

        foreach ($allOf as $branch) {
            $errors = [...$errors, ...$this->check($branch, $value, $path)];
        }

        foreach (['anyOf', 'oneOf'] as $keyword) {
            if (! is_array($schema[$keyword] ?? null)) {
                continue;
            }

            $passing = count(array_filter(
                $schema[$keyword],
                fn (array $branch): bool => $this->validate($branch, $value) === [],
            ));

            if ($keyword === 'anyOf' && $passing === 0) {
                $errors[] = $this->makeError($path, $keyword, 'Must match at least one of the allowed shapes.');
            }

            if ($keyword === 'oneOf' && $passing !== 1) {
                $errors[] = $this->makeError($path, $keyword, 'Must match exactly one of the allowed shapes.');
            }
        }

        return $errors;
    }

    private function matchesType(mixed $type, mixed $value): bool
    {
        foreach ((array) $type as $candidate) {
            $matches = match ($candidate) {
                'string' => is_string($value),
                'integer' => is_int($value),
                'number' => is_int($value) || is_float($value),
                'boolean' => is_bool($value),
                'null' => $value === null,
                'array' => is_array($value) && array_is_list($value),
                'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
                default => true,
            };

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    private function matchesPattern(string $pattern, string $value): bool
    {
        $delimited = '/'.str_replace('/', '\/', $pattern).'/u';

        // An invalid pattern returns false and is treated as a match, so it never blocks a payload.
        return @preg_match($delimited, $value) !== 0;
    }

    private function matchesFormat(string $format, string $value): bool
    {
        return match ($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'uri', 'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'ipv4' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            'ipv6' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            'uuid' => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1,
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false,
            'date-time' => strtotime($value) !== false,
            default => true,
        };
    }

    private function join(string $path, string $segment): string
    {
        return $path === '' ? $segment : "{$path}.{$segment}";
    }

    /**
     * @return SchemaError
     */
    private function makeError(string $path, string $rule, string $message): array
    {
        return ['path' => $path, 'rule' => $rule, 'message' => $message];
    }
}
