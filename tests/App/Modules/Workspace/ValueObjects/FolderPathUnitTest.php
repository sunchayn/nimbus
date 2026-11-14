<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\ValueObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

#[CoversClass(FolderPath::class)]
#[CoversClass(WorkspaceException::class)]
class FolderPathUnitTest extends TestCase
{
    public function test_it_turns_every_segment_into_a_slug(): void
    {
        // Act

        $path = FolderPath::from('Users Area\\Admin Panel');

        // Assert

        $this->assertSame(
            'users-area/admin-panel',
            (string) $path,
        );

        $this->assertSame(
            2,
            $path->depth(),
        );

        $this->assertSame(
            'admin-panel',
            $path->name(),
        );
    }

    public function test_it_drops_segments_that_cannot_survive_a_slug(): void
    {
        // Act

        $withReferences = FolderPath::from('users/../etc/.');
        $withSymbols = FolderPath::from('!!!');
        $withNothing = FolderPath::from(null);

        // Assert

        $this->assertSame(
            'users/etc',
            (string) $withReferences,
        );

        $this->assertTrue($withSymbols->isRoot());
        $this->assertTrue($withNothing->isRoot());

        $this->assertSame(
            '',
            (string) FolderPath::root(),
        );
    }

    public function test_it_limits_the_length_of_a_segment(): void
    {
        // Act

        $path = FolderPath::from(str_repeat('a', 100));

        // Assert

        $this->assertSame(
            60,
            strlen($path->name()),
        );
    }

    public function test_it_keeps_a_stored_path_as_it_is(): void
    {
        // Act

        $path = FolderPath::fromStored('My Folder/Inner');

        // Assert

        $this->assertSame(
            'My Folder/Inner',
            (string) $path,
        );

        $this->assertTrue(FolderPath::fromStored('.')->isRoot());
    }

    public function test_it_walks_up_and_down_a_path(): void
    {
        // Arrange

        $path = FolderPath::from('a/b/c');

        // Act

        $parent = $path->parent();
        $tail = $path->tail();
        $joined = $path->join('d');

        // Assert

        $this->assertSame(
            'a/b',
            (string) $parent,
        );

        $this->assertSame(
            'a',
            $path->head(),
        );

        $this->assertSame(
            'b/c',
            (string) $tail,
        );

        $this->assertSame(
            'a/b/c/d',
            (string) $joined,
        );

        $this->assertSame(
            'a/b/c',
            (string) $path,
        );
    }

    public function test_it_compares_two_paths_by_their_segments(): void
    {
        // Arrange

        $path = FolderPath::from('a/b/c');

        // Act

        $same = FolderPath::from('a/b/c');
        $shorter = FolderPath::from('a/b');

        // Assert

        $this->assertTrue($path->equals($same));
        $this->assertFalse($path->equals($shorter));
    }

    public function test_it_has_no_parent_above_the_root(): void
    {
        // Act

        $parent = FolderPath::root()->parent();

        // Assert

        $this->assertTrue($parent->isRoot());

        $this->assertSame(
            '',
            $parent->name(),
        );

        $this->assertSame(
            '',
            $parent->head(),
        );
    }

    public function test_it_refuses_a_path_that_is_too_deep(): void
    {
        // Assert

        $this->expectException(WorkspaceException::class);

        // Act

        FolderPath::from('a/b/c/d/e/f');
    }
}
