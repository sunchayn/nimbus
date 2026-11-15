<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Doctor\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnosticCheckData;
use Sunchayn\Nimbus\Modules\Doctor\Enums\DiagnosticStatusEnum;

#[CoversClass(DiagnosticCheckData::class)]
class DiagnosticCheckDataUnitTest extends TestCase
{
    public function test_it_turns_into_an_array(): void
    {
        // Arrange

        $check = new DiagnosticCheckData(
            name: 'Base URL',
            status: DiagnosticStatusEnum::Ok,
            message: 'It is reachable.',
        );

        // Act

        $array = $check->toArray();

        // Assert

        $this->assertSame(
            [
                'name' => 'Base URL',
                'status' => DiagnosticStatusEnum::Ok->value,
                'message' => 'It is reachable.',
            ],
            $array,
        );
    }
}
