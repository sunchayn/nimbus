<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ScanWorkspaceAction;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\WorkspaceScanResult;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ScanWorkspaceAction::class)]
#[CoversClass(WorkspaceException::class)]
class ScanWorkspaceActionFunctionalTest extends TestCase
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

    public function test_it_reads_an_empty_workspace_without_writing_the_manifest(): void
    {
        // Arrange

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            [],
            $workspace->entries->folderPaths()->all(),
        );

        $this->assertSame(
            [],
            $workspace->entries->requestFiles()->all(),
        );

        $this->assertFileDoesNotExist("{$this->workspacePath}/nimbus.json");
    }

    public function test_it_lists_folders_before_their_children_and_the_root_requests_first(): void
    {
        // Arrange

        $this->createFolder('users/admin');
        $this->createFolder('billing');

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-in-users',
                name: 'In users',
            ),
            hash: null,
            folder: 'users',
        );

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-at-root',
                name: 'At root',
            ),
            hash: null,
        );

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            ['users', 'users/admin', 'billing'],
            $workspace->entries->folderPaths()->map(strval(...))->all(),
        );

        $this->assertSame(
            ['request-at-root', 'request-in-users'],
            $this->requestIdsOf($workspace),
        );
    }

    public function test_it_keeps_the_saved_order_and_puts_new_entries_last_by_name(): void
    {
        // Arrange

        mkdir("{$this->workspacePath}/requests/delta", 0755, true);

        mkdir("{$this->workspacePath}/requests/charlie", 0755, true);

        mkdir("{$this->workspacePath}/requests/alpha", 0755, true);

        mkdir("{$this->workspacePath}/requests/bravo", 0755, true);

        file_put_contents(
            "{$this->workspacePath}/nimbus.json",
            json_encode(['folders' => ['' => ['delta', 'charlie']]]),
        );

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            ['delta', 'charlie', 'alpha', 'bravo'],
            $workspace->entries->folderPaths()->map(strval(...))->all(),
        );

        $this->assertSame(
            ['' => ['delta', 'charlie', 'alpha', 'bravo']],
            $workspace->manifest->folders,
        );
    }

    public function test_it_puts_new_requests_last_by_name(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-zeta',
                name: 'Zeta',
            ),
            hash: null,
        );

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-mike',
                name: 'Mike',
            ),
            hash: null,
        );

        unlink("{$this->workspacePath}/nimbus.json");

        $this->saveRequest(
            $this->savedRequest(
                id: 'request-alpha',
                name: 'Alpha',
            ),
            hash: null,
        );

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            ['request-mike', 'request-zeta', 'request-alpha'],
            $this->requestIdsOf($workspace),
        );
    }

    public function test_it_drops_entries_that_are_gone_from_the_disk_and_saves_the_manifest(): void
    {
        // Arrange

        $this->createFolder('users');

        file_put_contents(
            "{$this->workspacePath}/nimbus.json",
            json_encode([
                'folders' => ['' => ['gone', 'users'], 'gone' => ['child']],
                'requests' => ['users' => ['missing-request'], 'gone' => ['other']],
            ]),
        );

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $action->execute();

        // Assert

        $this->assertSame(
            ['folders' => ['' => ['users']], 'requests' => []],
            $this->readManifest(),
        );
    }

    public function test_it_writes_the_manifest_only_when_it_changed(): void
    {
        // Arrange

        $this->createFolder('users');

        $manifestPath = "{$this->workspacePath}/nimbus.json";

        touch($manifestPath, time() - 1000);

        clearstatcache();

        $modified = filemtime($manifestPath);

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $action->execute();
        $action->execute();

        // Assert

        clearstatcache();

        $this->assertSame(
            $modified,
            filemtime($manifestPath),
        );
    }

    public function test_it_still_answers_when_the_manifest_cannot_be_written(): void
    {
        // Arrange

        mkdir("{$this->workspacePath}/requests/users", 0755, true);

        mkdir("{$this->workspacePath}/nimbus.json");

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            ['users'],
            $workspace->entries->folderPaths()->map(strval(...))->all(),
        );
    }

    public function test_it_keeps_numeric_folder_names(): void
    {
        // Arrange

        $this->createFolder('2024');
        $this->createFolder('0');

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
            folder: '2024',
        );

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            ['2024', '0'],
            $workspace->entries->folderPaths()->map(strval(...))->all(),
        );

        $this->assertSame(
            ['2024' => ['request-1234']],
            $workspace->manifest->requests,
        );
    }

    public function test_it_skips_a_broken_request_file(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        file_put_contents("{$this->workspacePath}/requests/broken.request.json", '{ nope');

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertCount(
            1,
            $workspace->entries->requestFiles(),
        );

        $this->assertSame(
            [],
            $workspace->entries->folderPaths()->all(),
        );
    }

    public function test_it_skips_a_file_that_is_not_a_request_file(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        file_put_contents("{$this->workspacePath}/requests/notes.txt", 'notes');

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            ['request-1234'],
            $this->requestIdsOf($workspace),
        );

        $this->assertSame(
            [],
            $workspace->entries->folderPaths()->all(),
        );
    }

    public function test_it_skips_a_request_file_over_the_size_limit(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $oversizedContents = (string) json_encode([
            'id' => 'request-big',
            'name' => 'Big',
            'padding' => str_repeat('a', 1_100_000),
        ]);

        file_put_contents("{$this->workspacePath}/requests/big.request.json", $oversizedContents);

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertSame(
            ['request-1234'],
            $this->requestIdsOf($workspace),
        );
    }

    public function test_it_hashes_the_content_of_a_request_file(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $requestFile = $workspace->entries->findRequestOrFail('request-1234');

        $this->assertSame(
            hash_file('xxh3', $requestFile->filepath),
            $requestFile->hash,
        );

        $this->assertSame(
            'requests/create-user.request.json',
            $requestFile->relativeFilepath,
        );
    }

    public function test_it_skips_the_manifest_write_while_another_process_holds_the_lock(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        unlink("{$this->workspacePath}/nimbus.json");

        $handle = fopen(resolve(WorkspaceConfig::class)->lockPath(), 'c');

        flock($handle, LOCK_EX);

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        $this->assertCount(
            1,
            $workspace->entries->requestFiles(),
        );

        $this->assertFileDoesNotExist("{$this->workspacePath}/nimbus.json");

        flock($handle, LOCK_UN);

        fclose($handle);

        $action->execute();

        $this->assertFileExists("{$this->workspacePath}/nimbus.json");
    }

    public function test_it_ignores_a_request_file_that_is_a_link(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $outside = sys_get_temp_dir().'/outside-'.uniqid().'.request.json';

        file_put_contents(
            $outside,
            (string) json_encode(['id' => 'request-7777', 'name' => 'Outside']),
        );

        symlink($outside, "{$this->workspacePath}/requests/outside.request.json");

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        unlink($outside);

        $this->assertSame(
            ['request-1234'],
            $this->requestIdsOf($workspace),
        );
    }

    public function test_it_does_not_follow_a_linked_folder(): void
    {
        // Arrange

        $this->saveRequest(
            $this->savedRequest(),
            hash: null,
        );

        $outside = sys_get_temp_dir().'/nimbus-outside-'.uniqid();

        mkdir($outside);

        file_put_contents(
            "{$outside}/outside.request.json",
            (string) json_encode(['id' => 'request-7777', 'name' => 'Outside']),
        );

        symlink($outside, "{$this->workspacePath}/requests/linked");

        $action = resolve(ScanWorkspaceAction::class);

        // Act

        $workspace = $action->execute();

        // Assert

        unlink("{$this->workspacePath}/requests/linked");
        unlink("{$outside}/outside.request.json");
        rmdir($outside);

        $this->assertSame(
            ['request-1234'],
            $this->requestIdsOf($workspace),
        );

        $this->assertSame(
            [],
            $workspace->entries->folderPaths()->all(),
        );
    }

    public function test_it_refuses_to_scan_when_the_workspace_is_disabled(): void
    {
        // Arrange

        config()->set(
            'nimbus.workspace.enabled',
            false,
        );

        $action = resolve(ScanWorkspaceAction::class);

        // Assert

        $this->expectException(WorkspaceException::class);
        $this->expectExceptionMessage('The workspace is disabled.');

        // Act

        $action->execute();
    }

    /**
     * @return list<string>
     */
    private function requestIdsOf(WorkspaceScanResult $workspace): array
    {
        return $workspace
            ->entries
            ->requestFiles()
            ->map(static fn (RequestFile $requestFile): string => $requestFile->savedRequest->id)
            ->values()
            ->all();
    }
}
