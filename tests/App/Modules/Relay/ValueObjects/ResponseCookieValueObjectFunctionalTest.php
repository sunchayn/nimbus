<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\ValueObjects;

use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResponseCookieValueObject::class)]
class ResponseCookieValueObjectFunctionalTest extends TestCase
{
    public function test_it_computes_decrypted_value(): void
    {
        // Arrange

        $prefix = fake()->word();

        $unencryptedValue = fake()->uuid();

        $rawValue = encrypt($prefix.$unencryptedValue, serialize: false);

        // Act

        $actual = new ResponseCookieValueObject('foobar', rawValue: $rawValue, prefix: $prefix);

        // Assert

        $this->assertEquals(
            $unencryptedValue,
            invade($actual)->decryptedValue,
        );
    }

    public function test_it_coverts_to_array(): void
    {
        // Arrange

        $prefix = fake()->word();
        $unencryptedValue = fake()->uuid();
        $rawValue = base64_encode($unencryptedValue); // <- Dummy value.

        // Mocked so that the __constructor is not called. We want to pretend it is already constructed.
        $invadedInstance = invade(Mockery::mock(ResponseCookieValueObject::class)->makePartial());
        $invadedInstance->key = $key = fake()->word();
        $invadedInstance->rawValue = $rawValue;
        $invadedInstance->decryptedValue = $unencryptedValue;
        $invadedInstance->prefix = $prefix;

        // Act

        $actual = $invadedInstance->toArray();

        // Assert

        $this->assertEquals(
            [
                'key' => $key,
                'value' => [
                    'raw' => $rawValue,
                    'decrypted' => $unencryptedValue,
                ],
            ],
            $actual,
        );
    }
}
