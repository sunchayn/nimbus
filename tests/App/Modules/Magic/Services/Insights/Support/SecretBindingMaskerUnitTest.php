<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Support;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\SecretBindingMasker;

#[CoversClass(SecretBindingMasker::class)]
class SecretBindingMaskerUnitTest extends TestCase
{
    /**
     * @param  list<mixed>  $bindings
     * @param  list<mixed>  $expected
     */
    #[DataProvider('queriesDataProvider')]
    public function test_it_masks_the_bindings_of_secret_columns(string $sql, array $bindings, array $expected): void
    {
        // Arrange

        $masker = new SecretBindingMasker;

        // Act & Assert

        $this->assertSame($expected, $masker->mask($sql, $bindings));
    }

    public static function queriesDataProvider(): Generator
    {
        $mask = SecretBindingMasker::MASK;

        yield 'comparison' => [
            'sql' => 'select * from `users` where `email` = ? and `password` = ?',
            'bindings' => ['a@b.c', 'hunter2'],
            'expected' => ['a@b.c', $mask],
        ];

        yield 'update' => [
            'sql' => 'update `users` set `remember_token` = ?, `name` = ? where `id` = ?',
            'bindings' => ['abc', 'Ada', 1],
            'expected' => [$mask, 'Ada', 1],
        ];

        yield 'insert' => [
            'sql' => 'insert into "users" ("name", "api_key", "email") values (?, ?, ?)',
            'bindings' => ['Ada', 'k', 'a@b.c'],
            'expected' => ['Ada', $mask, 'a@b.c'],
        ];

        yield 'insert ignore' => [
            'sql' => 'insert ignore into users (name, secret) values (?, ?)',
            'bindings' => ['Ada', 's'],
            'expected' => ['Ada', $mask],
        ];

        yield 'multi row insert' => [
            'sql' => 'insert into users (name, secret) values (?, ?), (?, ?)',
            'bindings' => ['A', 's1', 'B', 's2'],
            'expected' => ['A', $mask, 'B', $mask],
        ];

        yield 'in list' => [
            'sql' => 'select * from tokens where token in (?, ?)',
            'bindings' => ['x', 'y'],
            'expected' => [$mask, $mask],
        ];

        yield 'not in list' => [
            'sql' => 'select * from users where password not in (?, ?)',
            'bindings' => ['x', 'y'],
            'expected' => [$mask, $mask],
        ];

        yield 'in list of a normal column' => [
            'sql' => 'select * from users where id in (?, ?)',
            'bindings' => [1, 2],
            'expected' => [1, 2],
        ];

        yield 'not equal' => [
            'sql' => 'select * from users where password != ?',
            'bindings' => ['x'],
            'expected' => [$mask],
        ];

        yield 'like on a secret column' => [
            'sql' => 'select * from users where password like ?',
            'bindings' => ['%x%'],
            'expected' => [$mask],
        ];

        yield 'like on a normal column' => [
            'sql' => 'select * from users where name like ?',
            'bindings' => ['%ada%'],
            'expected' => ['%ada%'],
        ];

        yield 'qualified column' => [
            'sql' => 'select * from users where users.password = ?',
            'bindings' => ['x'],
            'expected' => [$mask],
        ];

        yield 'double quoted column' => [
            'sql' => 'select * from users where "password" = ?',
            'bindings' => ['x'],
            'expected' => [$mask],
        ];

        yield 'bracket quoted column' => [
            'sql' => 'select * from users where [password] = ?',
            'bindings' => ['x'],
            'expected' => [$mask],
        ];

        yield 'upper case column' => [
            'sql' => 'select * from users where API_KEY = ?',
            'bindings' => ['x'],
            'expected' => [$mask],
        ];

        yield 'authorization column' => [
            'sql' => 'select * from calls where authorization = ?',
            'bindings' => ['Bearer x'],
            'expected' => [$mask],
        ];

        yield 'credential column' => [
            'sql' => 'select * from calls where credentials = ?',
            'bindings' => ['x'],
            'expected' => [$mask],
        ];

        yield 'masks a null binding' => [
            'sql' => 'select * from users where password = ?',
            'bindings' => [null],
            'expected' => [$mask],
        ];

        yield 'placeholder without a known column' => [
            'sql' => 'select ? as value',
            'bindings' => ['x'],
            'expected' => ['x'],
        ];

        yield 'more bindings than placeholders' => [
            'sql' => 'select * from users where password = ?',
            'bindings' => ['x', 'extra'],
            'expected' => [$mask, 'extra'],
        ];

        yield 'no secret column' => [
            'sql' => 'select * from users where email = ? and id = ?',
            'bindings' => ['a@b.c', 1],
            'expected' => ['a@b.c', 1],
        ];

        yield 'no bindings' => [
            'sql' => 'select 1',
            'bindings' => [],
            'expected' => [],
        ];
    }
}
