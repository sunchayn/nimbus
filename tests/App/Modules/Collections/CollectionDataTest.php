<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Collections;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Collections\DataTransferObjects\CollectionData;

#[CoversClass(CollectionData::class)]
class CollectionDataTest extends TestCase
{
    public function test_from_array_reads_all_fields(): void
    {
        $data = CollectionData::fromArray([
            'id' => 'flow-1',
            'name' => 'Registration',
            'order' => 3,
            'variables' => [['key' => 'token', 'value' => 'abc']],
            'requests' => [['id' => 'r1', 'name' => 'Create', 'method' => 'POST']],
        ]);

        $this->assertSame('flow-1', $data->id);
        $this->assertSame('Registration', $data->name);
        $this->assertSame(3, $data->order);
        $this->assertSame('token', $data->variables[0]['key']);
        $this->assertSame('r1', $data->requests[0]['id']);
    }

    public function test_from_array_applies_fallback_order_and_empties(): void
    {
        $data = CollectionData::fromArray(['id' => 'flow-1', 'name' => 'Reg'], order: 5);

        $this->assertSame(5, $data->order);
        $this->assertSame([], $data->variables);
        $this->assertSame([], $data->requests);
    }

    public function test_from_array_reindexes_variables_and_requests(): void
    {
        $data = CollectionData::fromArray([
            'id' => 'flow-1',
            'name' => 'Reg',
            'variables' => [2 => ['key' => 'a'], 5 => ['key' => 'b']],
            'requests' => [7 => ['id' => 'r']],
        ]);

        $this->assertSame([0, 1], array_keys($data->variables));
        $this->assertSame([0], array_keys($data->requests));
    }

    public function test_from_array_coerces_null_variable_keys_and_values_to_strings(): void
    {
        $data = CollectionData::fromArray([
            'id' => 'flow-1',
            'name' => 'Reg',
            'variables' => [
                ['id' => 1, 'type' => 'text', 'key' => null, 'value' => null, 'enabled' => true],
                'not-an-array',
            ],
        ]);

        $this->assertCount(1, $data->variables);
        $this->assertSame('', $data->variables[0]['key']);
        $this->assertSame('', $data->variables[0]['value']);
    }

    public function test_to_storage_array_includes_order_and_requests(): void
    {
        $data = new CollectionData('flow-1', 'Reg', [['key' => 'a']], [['id' => 'r']], 2);

        $this->assertSame(
            ['id', 'name', 'order', 'variables', 'requests'],
            array_keys($data->toStorageArray()),
        );
        $this->assertSame(2, $data->toStorageArray()['order']);
    }

    public function test_to_frontend_array_omits_order(): void
    {
        $data = new CollectionData('flow-1', 'Reg', [], [], 2);

        $frontend = $data->toFrontendArray();

        $this->assertSame(['id', 'name', 'variables', 'requests'], array_keys($frontend));
        $this->assertArrayNotHasKey('order', $frontend);
    }
}
