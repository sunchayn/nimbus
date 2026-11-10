<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Doctor\DoctorController;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(DoctorController::class)]
class DoctorTest extends TestCase
{
    public function test_it_returns_the_checks(): void
    {
        // Arrange

        Http::fake(['*' => Http::response('ok')]);

        // Act

        $response = $this->getJson(route('nimbus.api.doctor'));

        // Assert

        $response->assertOk();
        $response->assertJsonCount(6);
        $response->assertJsonFragment(['name' => 'Config', 'status' => 'ok', 'message' => 'The configuration values are valid.']);
    }
}
