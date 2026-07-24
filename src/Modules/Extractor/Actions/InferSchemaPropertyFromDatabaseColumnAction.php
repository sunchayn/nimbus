<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use Illuminate\Database\Eloquent\Model;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Infer a SchemaPropertyInterface of an Eloquent model's column via database schema lookup.
 *
 * @example execute('App\Models\User', 'email') // -> StringSchemaProperty
 * @example execute('App\Models\User', 'age')   // -> IntegerSchemaProperty
 *
 * @final
 */
class InferSchemaPropertyFromDatabaseColumnAction
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private static array $memo = [];

    /**
     * Resolves the schema property for a given model column.
     *
     * @param  class-string  $modelClass
     */
    public function execute(string $modelClass, string $column): ?SchemaPropertyInterface
    {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $model = new $modelClass;

        $columns = $this->getColumnsFromTable($model);

        foreach ($columns as $columnInfo) {
            if ($columnInfo['name'] === $column) {
                return $this->mapColumnToProperty($column, $columnInfo);
            }
        }

        return null;
    }

    /**
     * Maps database column info to a SchemaPropertyInterface instance.
     *
     * @param  array<string, mixed>  $columnInfo
     */
    private function mapColumnToProperty(string $name, array $columnInfo): SchemaPropertyInterface
    {
        $dbTypeName = $columnInfo['type_name'] ?? $columnInfo['type'] ?? 'string';

        $isNullable = $columnInfo['nullable'] ?? false;

        return match (strtolower((string) $dbTypeName)) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint' => new IntegerSchemaProperty($name, required: true, nullable: $isNullable),
            'float', 'double', 'decimal', 'numeric' => new NumberSchemaProperty($name, required: true, nullable: $isNullable),
            'bool', 'boolean' => new BooleanSchemaProperty($name, required: true, nullable: $isNullable),
            default => new StringSchemaProperty($name, required: true, nullable: $isNullable),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getColumnsFromTable(Model $model): array
    {
        $table = $model->getTable();

        $memoKey = sprintf('%s.%s', $model->getConnectionName(), $table);

        if (isset(self::$memo[$memoKey])) {
            return self::$memo[$memoKey];
        }

        $builder = $model->getConnection()->getSchemaBuilder();

        if (! $builder->hasTable($table)) {
            return self::$memo[$memoKey] = [];
        }

        return self::$memo[$memoKey] = $builder->getColumns($table);
    }

    /**
     * Clears static schema cache memory.
     */
    public static function clearMemo(): void
    {
        self::$memo = [];
    }
}
