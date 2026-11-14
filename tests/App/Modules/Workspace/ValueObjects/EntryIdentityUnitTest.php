<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\ValueObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\EntryIdentity;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

#[CoversClass(EntryIdentity::class)]
class EntryIdentityUnitTest extends TestCase
{
    public function test_it_builds_the_identity_of_a_folder_from_its_path(): void
    {
        // Act

        $identity = EntryIdentity::folder(FolderPath::fromStored('users/admin'));

        // Assert

        $this->assertSame(
            'folder|users|admin',
            (string) $identity,
        );
    }

    public function test_it_builds_the_identity_of_a_request_from_its_folder_and_id(): void
    {
        // Act

        $identity = EntryIdentity::request(
            FolderPath::fromStored('users'),
            'one',
        );

        // Assert

        $this->assertSame(
            'request|users|one',
            (string) $identity,
        );
    }

    public function test_it_tells_a_folder_and_a_request_with_the_same_name_apart(): void
    {
        // Act

        $folder = EntryIdentity::folder(FolderPath::fromStored('one'));

        $request = EntryIdentity::request(
            FolderPath::root(),
            'one',
        );

        // Assert

        $this->assertNotSame(
            (string) $folder,
            (string) $request,
        );
    }
}
