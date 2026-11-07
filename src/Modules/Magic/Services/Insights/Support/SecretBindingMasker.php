<?php

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support;

/**
 * Hides the bindings of columns whose names look like secrets.
 *
 * The column of a binding is read from the SQL text, for `column = ?` comparisons,
 * `column in (?, ?)` lists, and the column list of a single row insert.
 */
class SecretBindingMasker
{
    public const MASK = '********';

    private const SECRET_NAME = '/password|passwd|token|secret|api_?key|authorization|credential/i';

    private const IDENTIFIER = '[`"\[]?[\w.]+[`"\]]?';

    /**
     * @param  list<mixed>  $bindings
     * @return list<mixed>
     */
    public function mask(string $sql, array $bindings): array
    {
        if ($bindings === []) {
            return $bindings;
        }

        $columns = $this->columnsOfPlaceholders($sql);

        foreach (array_keys($bindings) as $position) {
            if (isset($columns[$position]) && preg_match(self::SECRET_NAME, $columns[$position]) === 1) {
                $bindings[$position] = self::MASK;
            }
        }

        return $bindings;
    }

    /**
     * @return array<int, string>
     */
    private function columnsOfPlaceholders(string $sql): array
    {
        $insertColumns = $this->insertColumns($sql);

        $columns = [];
        $offset = 0;
        $position = 0;

        while (($found = strpos($sql, '?', $offset)) !== false) {
            $before = substr($sql, 0, $found);

            $columns[$position] = $this->columnBefore($before) ?? $this->insertColumnAt($insertColumns, $position, $sql, $found) ?? '';

            $position++;
            $offset = $found + 1;
        }

        return $columns;
    }

    private function columnBefore(string $before): ?string
    {
        $comparison = '/('.self::IDENTIFIER.')\s*(?:=|!=|<>|<=|>=|<|>|(?:not\s+)?like)\s*$/i';
        $list = '/('.self::IDENTIFIER.')\s+(?:not\s+)?in\s*\((?:\s*\?\s*,)*\s*$/i';

        if (preg_match($comparison, $before, $matches) === 1 || preg_match($list, $before, $matches) === 1) {
            return trim($matches[1], '`"[]');
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function insertColumns(string $sql): array
    {
        if (preg_match('/^\s*insert\s+(?:ignore\s+)?into\s+\S+\s*\(([^)]*)\)\s*values/i', $sql, $matches) !== 1) {
            return [];
        }

        return array_map(fn (string $column): string => trim($column, ' `"[]'), explode(',', $matches[1]));
    }

    /**
     * @param  list<string>  $insertColumns
     */
    private function insertColumnAt(array $insertColumns, int $position, string $sql, int $found): ?string
    {
        if ($insertColumns === [] || ! preg_match('/values\s*\(/i', substr($sql, 0, $found))) {
            return null;
        }

        return $insertColumns[$position % count($insertColumns)] ?? null;
    }
}
