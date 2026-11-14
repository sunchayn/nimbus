<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class WorkspaceException extends RuntimeException
{
    private function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    public static function disabled(): self
    {
        return new self(
            'The workspace is disabled.',
            404,
        );
    }

    public static function requestNotFound(string $id): self
    {
        return new self(
            "The request `{$id}` does not exist in the workspace.",
            404,
        );
    }

    public static function conflict(): self
    {
        return new self(
            'The request file changed on disk since it was loaded.',
            409,
        );
    }

    public static function fileTooLarge(): self
    {
        return new self(
            'The request file is larger than 1 MB.',
            413,
        );
    }

    public static function unreadableRequest(): self
    {
        return new self(
            'The request file is not a valid request.',
            422,
        );
    }

    public static function invalidFolder(): self
    {
        return new self(
            'The folder name is empty after removing special characters.',
            422,
        );
    }

    public static function folderNotFound(string $folder): self
    {
        return new self(
            "The folder `{$folder}` does not exist in the workspace.",
            404,
        );
    }

    public static function folderExists(string $folder): self
    {
        return new self(
            "A folder or a file named `{$folder}` already exists in the workspace.",
            422,
        );
    }

    public static function folderTooDeep(int $depth): self
    {
        return new self(
            "Folders can be nested {$depth} levels deep at most.",
            422,
        );
    }

    public static function writeFailed(): self
    {
        return new self(
            'The workspace file could not be written.',
            500,
        );
    }

    public function render(): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage()],
            $this->status,
        );
    }
}
