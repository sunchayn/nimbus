<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaPropertyFromDatabaseColumnAction;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(InferSchemaPropertyFromDatabaseColumnAction::class)]
class InferSchemaPropertyFromDatabaseColumnActionFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SchemaFacade::create(
            'dummy_introspection_table',
            function ($table) {
                $table->increments('id');
                $table->string('email')->nullable();
                $table->integer('age');
                $table->decimal('price', 8, 2);
                $table->boolean('active');
            },
        );
    }

    protected function tearDown(): void
    {
        SchemaFacade::dropIfExists('dummy_introspection_table');

        InferSchemaPropertyFromDatabaseColumnAction::clearMemo();

        parent::tearDown();
    }

    public function test_it_resolves_column_types_from_database(): void
    {
        // Arrange

        $action = new InferSchemaPropertyFromDatabaseColumnAction;

        // Act & Assert

        $this->assertEquals(new IntegerSchemaProperty('id', required: true), $action->execute(DummyModelForIntrospection::class, 'id'));
        $this->assertEquals(new StringSchemaProperty('email', required: true, nullable: true), $action->execute(DummyModelForIntrospection::class, 'email'));
        $this->assertEquals(new IntegerSchemaProperty('age', required: true), $action->execute(DummyModelForIntrospection::class, 'age'));
        $this->assertEquals(new NumberSchemaProperty('price', required: true), $action->execute(DummyModelForIntrospection::class, 'price'));
        $this->assertEquals(new IntegerSchemaProperty('active', required: true), $action->execute(DummyModelForIntrospection::class, 'active'));
        $this->assertNull($action->execute(DummyModelForIntrospection::class, 'non_existent'));
    }

    #[DataProvider('edgeCasesDataProvider')]
    public function test_it_handles_edge_cases(
        string $class,
        string $column,
        ?SchemaPropertyInterface $expectedType,
        ?callable $setup = null
    ): void {
        // Arrange

        $action = new InferSchemaPropertyFromDatabaseColumnAction;

        if ($setup !== null) {
            $setup();
        }

        // Act

        $actual = $action->execute($class, $column);

        // Assert

        $this->assertEquals($expectedType, $actual);
    }

    public static function edgeCasesDataProvider(): Generator
    {
        yield 'non-existent class returns null' => [
            'class' => 'NonExistentModelClass',
            'column' => 'id',
            'expectedType' => null,
        ];

        yield 'class not a subclass of Model returns null' => [
            'class' => stdClass::class,
            'column' => 'id',
            'expectedType' => null,
        ];

        yield 'non-existent table returns null' => [
            'class' => DummyModelWithNoTable::class,
            'column' => 'id',
            'expectedType' => null,
        ];

        yield 'custom boolean column mapping via memoized introspection' => [
            'class' => DummyModelForIntrospection::class,
            'column' => 'active_bool',
            'expectedType' => new BooleanSchemaProperty('active_bool', required: true),
            'setup' => function (): void {
                $model = new DummyModelForIntrospection;

                $key = sprintf('%s.%s', $model->getConnectionName(), $model->getTable());

                invade(InferSchemaPropertyFromDatabaseColumnAction::class)
                    ->set('memo', [
                        $key => [
                            ['name' => 'active_bool', 'type_name' => 'boolean', 'nullable' => false],
                        ],
                    ]);
            },
        ];
    }
}

class DummyModelForIntrospection extends Model
{
    protected $table = 'dummy_introspection_table';
}

class DummyModelWithNoTable extends Model
{
    protected $table = 'non_existent_table';
}
