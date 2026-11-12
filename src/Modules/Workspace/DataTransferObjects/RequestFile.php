<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * A request file of the workspace, with the request it holds and the hash of its content.
 */
class RequestFile
{
    public function __construct(
        public readonly string $filepath,
        public readonly string $relativeFilepath,
        public readonly FolderPath $folder,
        public readonly SavedRequest $savedRequest,
        public readonly string $hash,
    ) {}

    /**
     * The request and its hash, as the API returns them.
     *
     * @return array{request: array<string, mixed>, hash: string}
     */
    public function toArray(): array
    {
        return [
            'request' => $this->savedRequest->toArray(),
            'hash' => $this->hash,
        ];
    }
}
