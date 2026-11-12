<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects;

use InvalidArgumentException;
use Sunchayn\Nimbus\Modules\Workspace\Enums\EntryKind;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\EntryIdentity;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * A folder or a request file in the workspace tree, listed under the folder that holds it.
 */
class WorkspaceEntry
{
    /**
     * @param  string  $key  The folder name of a folder, or the request id of a request.
     * @param  RequestFile|null  $requestFile  Only a request has a file.
     */
    public function __construct(
        public readonly EntryKind $kind,
        public readonly FolderPath $parent,
        public readonly string $key,
        public readonly ?RequestFile $requestFile = null,
    ) {
        if ($kind === EntryKind::Folder && $requestFile instanceof RequestFile) {
            throw new InvalidArgumentException('A folder entry has no request file.');
        }

        if ($kind === EntryKind::Request && ! $requestFile instanceof RequestFile) {
            throw new InvalidArgumentException('A request entry needs a request file.');
        }
    }

    public static function folder(FolderPath $folder): self
    {
        return new self(
            kind: EntryKind::Folder,
            parent: $folder->parent(),
            key: $folder->name(),
        );
    }

    public static function request(RequestFile $requestFile): self
    {
        return new self(
            kind: EntryKind::Request,
            parent: $requestFile->folder,
            key: $requestFile->savedRequest->id,
            requestFile: $requestFile,
        );
    }

    /**
     * The same identity the manifest builds for the folder or request it lists.
     */
    public function identity(): EntryIdentity
    {
        if ($this->isFolder()) {
            return EntryIdentity::folder($this->folderPath());
        }

        return EntryIdentity::request(
            $this->parent,
            $this->key,
        );
    }

    public function isFolder(): bool
    {
        return $this->kind === EntryKind::Folder;
    }

    /**
     * @phpstan-assert-if-true RequestFile $this->requestFile
     */
    public function isRequest(): bool
    {
        return $this->kind === EntryKind::Request;
    }

    /**
     * The path of a folder entry.
     */
    public function folderPath(): FolderPath
    {
        return $this->parent->join($this->key);
    }
}
