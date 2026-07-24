<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Response\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\DataTransferObjects\ExtractableResponseData;

#[CoversClass(ExtractableResponseData::class)]
class ExtractableResponseDataUnitTest extends TestCase
{
    public function test_it_can_be_created_empty(): void
    {
        // Act

        $dto = ExtractableResponseData::empty();

        // Assert

        $this->assertNull($dto->route);

        $this->assertNull($dto->payload);
    }
}
