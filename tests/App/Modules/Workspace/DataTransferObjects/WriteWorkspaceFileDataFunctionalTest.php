<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ManifestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WriteWorkspaceFileData;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(WriteWorkspaceFileData::class)]
class WriteWorkspaceFileDataFunctionalTest extends TestCase
{
    use UsesTemporaryWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableWorkspace();
    }

    protected function tearDown(): void
    {
        $this->tearDownWorkspace();

        parent::tearDown();
    }

    public function test_it_targets_the_manifest_file_with_the_manifest_as_contents(): void
    {
        // Arrange

        $manifest = new ManifestFile(folders: ['' => ['users']]);

        // Act

        $data = WriteWorkspaceFileData::fromManifest($manifest);

        // Assert

        $this->assertSame(
            "{$this->workspacePath}/nimbus.json",
            $data->filepath,
        );

        $this->assertSame(
            $manifest,
            $data->contents,
        );
    }
}
