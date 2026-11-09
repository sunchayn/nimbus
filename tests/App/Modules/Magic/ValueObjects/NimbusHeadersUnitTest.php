<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\ValueObjects;

use Generator;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\ValueObjects\NimbusHeaders;

#[CoversClass(NimbusHeaders::class)]
class NimbusHeadersUnitTest extends TestCase
{
    #[DataProvider('actorDataProvider')]
    public function test_it_parses_the_actor_header(?string $value, ?string $guard, ?string $key): void
    {
        // Arrange

        $request = Request::create('/test');

        if ($value !== null) {
            $request->headers->set(NimbusHeaders::ACTOR, $value);
        }

        // Act

        $headers = NimbusHeaders::fromRequest($request);

        // Assert

        $this->assertSame($guard, $headers->actorGuard);
        $this->assertSame($key, $headers->actorKey);
        $this->assertSame($guard !== null, $headers->hasActor());
    }

    public static function actorDataProvider(): Generator
    {
        yield 'missing' => [null, null, null];
        yield 'integer key' => ['web:12', 'web', '12'];
        yield 'ulid key' => ['sanctum:01HZX3Y8K2M4N6P8Q0R2S4T6V8', 'sanctum', '01HZX3Y8K2M4N6P8Q0R2S4T6V8'];
        yield 'key with colon' => ['web:a:b', 'web', 'a:b'];
        yield 'no separator' => ['web', null, null];
        yield 'empty guard' => [':12', null, null];
        yield 'empty key' => ['web:', null, null];
    }

    public function test_it_reads_transaction_mode(): void
    {
        // Arrange

        $request = Request::create('/test');
        $request->headers->set(NimbusHeaders::TRANSACTION_MODE, '1');

        // Act & Assert

        $this->assertTrue(NimbusHeaders::fromRequest($request)->transactionMode);
        $this->assertFalse(NimbusHeaders::fromRequest(Request::create('/test'))->transactionMode);
    }

    public function test_it_builds_the_actor_value(): void
    {
        // Act & Assert

        $this->assertSame('web:7', NimbusHeaders::actorValue('web', '7'));
    }
}
