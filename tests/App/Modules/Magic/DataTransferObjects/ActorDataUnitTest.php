<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\ActorData;

#[CoversClass(ActorData::class)]
class ActorDataUnitTest extends TestCase
{
    public function test_it_turns_into_an_array(): void
    {
        // Arrange

        $actor = new ActorData(
            id: '12',
            label: 'Jane Doe',
            model: 'App\Models\User',
        );

        // Act

        $array = $actor->toArray();

        // Assert

        $this->assertSame(
            [
                'id' => '12',
                'label' => 'Jane Doe',
                'model' => 'App\Models\User',
            ],
            $array,
        );
    }
}
