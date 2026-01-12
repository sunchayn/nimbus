<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Enums;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(StringFormat::class)]
class StringFormatUnitTest extends TestCase
{
    #[DataProvider('ruleDataProvider')]
    public function test_from_rule(string $rule, ?StringFormat $expected): void
    {
        // Act

        $result = StringFormat::fromRule($rule);

        // Assert

        $this->assertEquals($expected, $result);
    }

    public static function ruleDataProvider(): Generator
    {
        yield 'uuid rule' => [
            'rule' => 'uuid',
            'expected' => StringFormat::UUID,
        ];

        yield 'email rule' => [
            'rule' => 'email',
            'expected' => StringFormat::EMAIL,
        ];

        yield 'date rule' => [
            'rule' => 'date',
            'expected' => StringFormat::DATE_TIME,
        ];

        yield 'url rule' => [
            'rule' => 'url',
            'expected' => StringFormat::URL,
        ];

        yield 'invalid rule' => [
            'rule' => 'invalid',
            'expected' => null,
        ];
    }
}
