<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightStore;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(InsightStore::class)]
class InsightStoreFunctionalTest extends TestCase
{
    private const ID = '11111111-2222-3333-4444-555555555555';

    private const OTHER_ID = '66666666-7777-8888-9999-000000000000';

    protected function tearDown(): void
    {
        $store = resolve(InsightStore::class);
        $store->pull(self::ID);
        $store->pull(self::OTHER_ID);

        parent::tearDown();
    }

    public function test_it_reads_a_file_once_and_deletes_it(): void
    {
        // Arrange

        $store = resolve(InsightStore::class);

        // Act

        $written = $store->write(self::ID, '{"queries":[]}');

        $first = $store->pull(self::ID);
        $second = $store->pull(self::ID);

        // Assert

        $this->assertTrue($written);
        $this->assertSame(['queries' => []], $first);
        $this->assertNull($second);
    }

    public function test_it_leaves_no_temporary_file_behind(): void
    {
        // Arrange

        $store = resolve(InsightStore::class);

        // Act

        $store->write(self::ID, '{}');

        // Assert

        $this->assertFileDoesNotExist($this->storagePath(self::ID.'.tmp'));
        $this->assertFileExists($this->storagePath(self::ID.'.json'));
    }

    public function test_it_replaces_the_file_when_the_same_id_is_written_twice(): void
    {
        // Arrange

        $store = resolve(InsightStore::class);

        // Act

        $store->write(self::ID, '{"version":1}');
        $store->write(self::ID, '{"version":2}');

        // Assert

        $this->assertSame(['version' => 2], $store->pull(self::ID));
    }

    #[DataProvider('invalidIdsDataProvider')]
    public function test_it_neither_writes_nor_reads_with_an_invalid_id(string $id): void
    {
        // Arrange

        $store = resolve(InsightStore::class);

        // Act

        $written = $store->write($id, '{}');

        // Assert

        $this->assertFalse($written);
        $this->assertNull($store->pull($id));
    }

    public static function invalidIdsDataProvider(): Generator
    {
        yield 'parent folder' => ['id' => '../evil'];

        yield 'nested path' => ['id' => 'aaaaaaaa/bbbbbbbb'];

        yield 'too short' => ['id' => 'short'];

        yield 'too long' => ['id' => str_repeat('a', 65)];

        yield 'dot' => ['id' => 'aaaaaaaa.json'];

        yield 'empty' => ['id' => ''];
    }

    #[DataProvider('idsDataProvider')]
    public function test_it_validates_ids(mixed $id, bool $expected): void
    {
        // Act & Assert

        $this->assertSame($expected, InsightStore::isValidId($id));
    }

    public static function idsDataProvider(): Generator
    {
        yield 'uuid' => [
            'id' => self::ID,
            'expected' => true,
        ];

        yield 'shortest allowed' => [
            'id' => str_repeat('a', 8),
            'expected' => true,
        ];

        yield 'longest allowed' => [
            'id' => str_repeat('a', 64),
            'expected' => true,
        ];

        yield 'null' => [
            'id' => null,
            'expected' => false,
        ];

        yield 'array from a repeated header' => [
            'id' => [self::ID],
            'expected' => false,
        ];

        yield 'integer' => [
            'id' => 12345678,
            'expected' => false,
        ];

        yield 'trailing newline' => [
            'id' => self::ID."\n",
            'expected' => false,
        ];
    }

    #[DataProvider('unreadableContentDataProvider')]
    public function test_it_returns_null_when_the_content_is_not_a_json_object(string $content): void
    {
        // Arrange

        $store = resolve(InsightStore::class);
        $store->write(self::ID, $content);

        // Act & Assert

        $this->assertNull($store->pull(self::ID));
    }

    public static function unreadableContentDataProvider(): Generator
    {
        yield 'not json' => ['content' => 'not json'];

        yield 'json string' => ['content' => '"text"'];

        yield 'json number' => ['content' => '12'];

        yield 'empty file' => ['content' => ''];
    }

    public function test_it_deletes_the_file_even_when_it_cannot_be_decoded(): void
    {
        // Arrange

        $store = resolve(InsightStore::class);
        $store->write(self::ID, 'not json');

        // Act

        $store->pull(self::ID);

        // Assert

        $this->assertFileDoesNotExist($this->storagePath(self::ID.'.json'));
    }

    public function test_it_removes_files_nobody_read_after_ten_minutes(): void
    {
        // Arrange

        $store = resolve(InsightStore::class);
        $store->write(self::OTHER_ID, '{}');

        touch($this->storagePath(self::OTHER_ID.'.json'), time() - 601);

        // Act

        $store->write(self::ID, '{}');

        // Assert

        $this->assertFileDoesNotExist($this->storagePath(self::OTHER_ID.'.json'));
        $this->assertFileExists($this->storagePath(self::ID.'.json'));
    }

    public function test_it_keeps_recent_files_that_nobody_read_yet(): void
    {
        // Arrange

        $store = resolve(InsightStore::class);
        $store->write(self::OTHER_ID, '{}');

        touch($this->storagePath(self::OTHER_ID.'.json'), time() - 599);

        // Act

        $store->write(self::ID, '{}');

        // Assert

        $this->assertFileExists($this->storagePath(self::OTHER_ID.'.json'));
    }

    private function storagePath(string $file): string
    {
        return base_path('vendor/sunchayn/nimbus/storage/insight/'.$file);
    }
}
