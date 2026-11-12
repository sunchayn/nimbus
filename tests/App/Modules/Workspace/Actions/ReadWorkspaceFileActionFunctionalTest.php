<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Actions;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Workspace\Actions\ReadWorkspaceFileAction;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryWorkspace;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ReadWorkspaceFileAction::class)]
class ReadWorkspaceFileActionFunctionalTest extends TestCase
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

    public function test_it_reads_the_text_of_a_file(): void
    {
        // Arrange

        $filepath = $this->makeFile(
            name: 'note.txt',
            contents: 'hello',
        );

        $action = resolve(ReadWorkspaceFileAction::class);

        // Act

        $contents = $action->execute(filepath: $filepath);

        // Assert

        $this->assertSame(
            'hello',
            $contents,
        );
    }

    public function test_it_gives_null_for_a_file_that_does_not_exist(): void
    {
        // Arrange

        $action = resolve(ReadWorkspaceFileAction::class);

        // Act

        $contents = $action->execute(filepath: "{$this->workspacePath}/missing.txt");

        // Assert

        $this->assertNull($contents);
    }

    public function test_it_gives_null_for_a_directory(): void
    {
        // Arrange

        File::ensureDirectoryExists("{$this->workspacePath}/folder");

        $action = resolve(ReadWorkspaceFileAction::class);

        // Act

        $contents = $action->execute(filepath: "{$this->workspacePath}/folder");

        // Assert

        $this->assertNull($contents);
    }

    public function test_it_gives_null_for_a_link(): void
    {
        // Arrange

        $target = $this->makeFile(
            name: 'target.txt',
            contents: 'hello',
        );

        symlink($target, "{$this->workspacePath}/link.txt");

        $action = resolve(ReadWorkspaceFileAction::class);

        // Act

        $contents = $action->execute(filepath: "{$this->workspacePath}/link.txt");

        // Assert

        $this->assertNull($contents);
    }

    public function test_it_gives_null_for_a_file_over_the_size_limit(): void
    {
        // Arrange

        $filepath = $this->makeFile(
            name: 'big.txt',
            contents: str_repeat('a', 1_048_577),
        );

        $action = resolve(ReadWorkspaceFileAction::class);

        // Act

        $contents = $action->execute(filepath: $filepath);

        // Assert

        $this->assertNull($contents);
    }

    public function test_it_reads_a_file_at_the_size_limit(): void
    {
        // Arrange

        $filepath = $this->makeFile(
            name: 'limit.txt',
            contents: str_repeat('a', 1_048_576),
        );

        $action = resolve(ReadWorkspaceFileAction::class);

        // Act

        $contents = $action->execute(filepath: $filepath);

        // Assert

        $this->assertSame(
            1_048_576,
            strlen((string) $contents),
        );
    }

    public function test_it_gives_null_for_an_empty_file(): void
    {
        // Arrange

        $filepath = $this->makeFile(
            name: 'empty.txt',
            contents: '',
        );

        $action = resolve(ReadWorkspaceFileAction::class);

        // Act

        $contents = $action->execute(filepath: $filepath);

        // Assert

        $this->assertNull($contents);
    }

    private function makeFile(string $name, string $contents): string
    {
        File::ensureDirectoryExists($this->workspacePath);

        $filepath = "{$this->workspacePath}/{$name}";

        file_put_contents($filepath, $contents);

        return $filepath;
    }
}
