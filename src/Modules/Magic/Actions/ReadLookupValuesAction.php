<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Reads a few existing values of a column, so exists rules can be filled with real data.
 *
 * Only pairs that appear in the allowed list are read. Each read selects one column and caps the
 * rows. A failed read gives an empty list.
 *
 * @final
 */
class ReadLookupValuesAction
{
    public const MAX_ROWS = 20;

    private const IDENTIFIER = '/^[A-Za-z_]\w*$/';

    /**
     * @param  array<int, array{table: string, column: string}>  $allowed
     * @param  array<int, array{table: string, column: string}>  $requested
     * @return array<string, array<int, int|string|float>>
     */
    public function execute(array $allowed, array $requested): array
    {
        $values = [];

        foreach ($requested as $pair) {
            if (! in_array($pair, $allowed, true) || ! $this->isReadable($pair['table'], $pair['column'])) {
                continue;
            }

            $values[self::key($pair['table'], $pair['column'])] = $this->read($pair['table'], $pair['column']);
        }

        return $values;
    }

    public static function key(string $table, string $column): string
    {
        return $table.'.'.$column;
    }

    private function isReadable(string $table, string $column): bool
    {
        return preg_match(self::IDENTIFIER, $table) === 1
            && preg_match(self::IDENTIFIER, $column) === 1;
    }

    /**
     * @return array<int, int|string|float>
     */
    private function read(string $table, string $column): array
    {
        try {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                return [];
            }

            return DB::table($table)
                ->whereNotNull($column)
                ->orderByDesc($column)
                ->limit(self::MAX_ROWS)
                ->pluck($column)
                ->filter(fn (mixed $value): bool => is_string($value) || is_int($value) || is_float($value))
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
