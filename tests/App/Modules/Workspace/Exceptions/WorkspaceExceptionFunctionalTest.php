<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\Exceptions;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(WorkspaceException::class)]
class WorkspaceExceptionFunctionalTest extends TestCase
{
    #[DataProvider('exceptions')]
    public function test_it_carries_the_http_status_and_the_message_of_the_failure(
        WorkspaceException $exception,
        int $status,
        string $message,
    ): void {
        // Assert

        $this->assertSame(
            $status,
            $exception->status,
        );

        $this->assertSame(
            $message,
            $exception->getMessage(),
        );
    }

    /**
     * @return Generator<string, array{exception: WorkspaceException, status: int, message: string}>
     */
    public static function exceptions(): Generator
    {
        yield 'disabled' => [
            'exception' => WorkspaceException::disabled(),
            'status' => 404,
            'message' => 'The workspace is disabled.',
        ];

        yield 'request not found' => [
            'exception' => WorkspaceException::requestNotFound('request-1'),
            'status' => 404,
            'message' => 'The request `request-1` does not exist in the workspace.',
        ];

        yield 'conflict' => [
            'exception' => WorkspaceException::conflict(),
            'status' => 409,
            'message' => 'The request file changed on disk since it was loaded.',
        ];

        yield 'file too large' => [
            'exception' => WorkspaceException::fileTooLarge(),
            'status' => 413,
            'message' => 'The request file is larger than 1 MB.',
        ];

        yield 'unreadable request' => [
            'exception' => WorkspaceException::unreadableRequest(),
            'status' => 422,
            'message' => 'The request file is not a valid request.',
        ];

        yield 'invalid folder' => [
            'exception' => WorkspaceException::invalidFolder(),
            'status' => 422,
            'message' => 'The folder name is empty after removing special characters.',
        ];

        yield 'folder not found' => [
            'exception' => WorkspaceException::folderNotFound('users'),
            'status' => 404,
            'message' => 'The folder `users` does not exist in the workspace.',
        ];

        yield 'folder exists' => [
            'exception' => WorkspaceException::folderExists('users'),
            'status' => 422,
            'message' => 'A folder or a file named `users` already exists in the workspace.',
        ];

        yield 'folder too deep' => [
            'exception' => WorkspaceException::folderTooDeep(5),
            'status' => 422,
            'message' => 'Folders can be nested 5 levels deep at most.',
        ];

        yield 'write failed' => [
            'exception' => WorkspaceException::writeFailed(),
            'status' => 500,
            'message' => 'The workspace file could not be written.',
        ];
    }

    public function test_it_renders_a_json_response_with_its_message_and_status(): void
    {
        // Arrange

        $exception = WorkspaceException::conflict();

        // Act

        $response = $exception->render();

        // Assert

        $this->assertSame(
            409,
            $response->getStatusCode(),
        );

        $this->assertSame(
            ['message' => 'The request file changed on disk since it was loaded.'],
            $response->getData(true),
        );
    }
}
