<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Http\Api\Workspace\Rules;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Http\Api\Workspace\Rules\WorkspaceFolderRule;

#[CoversClass(WorkspaceFolderRule::class)]
class WorkspaceFolderRuleUnitTest extends TestCase
{
    #[DataProvider('validProvider')]
    public function test_it_accepts_a_folder(string $value, bool $nested): void
    {
        // Act

        $failures = $this->validate(
            $value,
            $nested,
        );

        // Assert

        $this->assertSame(
            [],
            $failures,
        );
    }

    /**
     * @return Generator<string, array{value: string, nested: bool}>
     */
    public static function validProvider(): Generator
    {
        yield 'a name' => [
            'value' => 'Users',
            'nested' => true,
        ];

        yield 'a path' => [
            'value' => 'users/admin',
            'nested' => true,
        ];

        yield 'a windows path' => [
            'value' => 'users\\admin',
            'nested' => true,
        ];

        yield 'special characters next to letters' => [
            'value' => 'My users!',
            'nested' => true,
        ];

        yield 'empty segments' => [
            'value' => 'users//admin/',
            'nested' => true,
        ];

        yield 'five levels' => [
            'value' => 'a/b/c/d/e',
            'nested' => true,
        ];

        yield 'a single name' => [
            'value' => 'People',
            'nested' => false,
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_it_refuses_a_folder(string $value, bool $nested, string $message): void
    {
        // Act

        $failures = $this->validate(
            $value,
            $nested,
        );

        // Assert

        $this->assertCount(
            1,
            $failures,
        );

        $this->assertStringContainsString(
            $message,
            $failures[0],
        );
    }

    /**
     * @return Generator<string, array{value: string, nested: bool, message: string}>
     */
    public static function invalidProvider(): Generator
    {
        yield 'only slashes' => [
            'value' => '///',
            'nested' => true,
            'message' => 'must have a folder name',
        ];

        yield 'only spaces' => [
            'value' => '   ',
            'nested' => true,
            'message' => 'must have a folder name',
        ];

        yield 'a parent reference' => [
            'value' => 'users/../etc',
            'nested' => true,
            'message' => 'at least one letter or number',
        ];

        yield 'a current folder reference' => [
            'value' => './users',
            'nested' => true,
            'message' => 'at least one letter or number',
        ];

        yield 'only symbols' => [
            'value' => '!!!',
            'nested' => true,
            'message' => 'at least one letter or number',
        ];

        yield 'six levels' => [
            'value' => 'a/b/c/d/e/f',
            'nested' => true,
            'message' => 'more than 5 levels',
        ];

        yield 'a long name' => [
            'value' => str_repeat('a', 61),
            'nested' => true,
            'message' => 'longer than 60',
        ];

        yield 'a slash in a single name' => [
            'value' => 'a/b',
            'nested' => false,
            'message' => 'may not contain slashes',
        ];
    }

    public function test_it_refuses_a_value_that_is_not_a_string(): void
    {
        // Arrange

        $failures = [];

        // Act

        (new WorkspaceFolderRule)->validate(
            attribute: 'folder',
            value: ['users'],
            fail: function (string $message) use (&$failures): void {
                $failures[] = $message;
            },
        );

        // Assert

        $this->assertSame(
            ['The :attribute must be a string.'],
            $failures,
        );
    }

    /**
     * @return list<string>
     */
    private function validate(string $value, bool $nested): array
    {
        $failures = [];

        (new WorkspaceFolderRule(isNested: $nested))->validate(
            attribute: 'folder',
            value: $value,
            fail: function (string $message) use (&$failures): void {
                $failures[] = $message;
            },
        );

        return $failures;
    }
}
