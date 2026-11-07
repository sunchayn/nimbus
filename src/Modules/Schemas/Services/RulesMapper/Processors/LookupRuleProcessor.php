<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors;

/**
 * Processes the exists and unique rules, which point at a database table.
 */
class LookupRuleProcessor
{
    /**
     * @param  string[]  $params
     * @return array{hints?: string[], lookup?: array{table: string, column: string}}
     */
    public static function process(string $name, array $params, ?string $field): array
    {
        if (! in_array($name, ['exists', 'unique'], true) || ($params[0] ?? '') === '') {
            return [];
        }

        $table = $params[0];
        $column = ($params[1] ?? '') !== '' ? $params[1] : (string) $field;

        $hint = sprintf('%s in %s.%s', $name === 'exists' ? 'must exist' : 'must be unique', $table, $column);

        // A model class needs the app to resolve its table, so only the hint is kept.
        if ($name === 'unique' || str_contains($table, '\\') || $column === '') {
            return ['hints' => [$hint]];
        }

        return [
            'hints' => [$hint],
            'lookup' => ['table' => $table, 'column' => $column],
        ];
    }
}
