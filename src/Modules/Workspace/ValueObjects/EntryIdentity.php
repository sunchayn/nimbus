<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\ValueObjects;

use Stringable;
use Sunchayn\Nimbus\Modules\Workspace\Enums\EntryKind;

/**
 * Tells two workspace entries apart, a folder from a request even when they share the same key.
 */
final readonly class EntryIdentity implements Stringable
{
    private function __construct(
        private EntryKind $kind,
        private FolderPath $parent,
        private string $key,
    ) {}

    public static function folder(FolderPath $folder): self
    {
        return new self(
            kind: EntryKind::Folder,
            parent: $folder->parent(),
            key: $folder->name(),
        );
    }

    public static function request(FolderPath $folder, string $requestId): self
    {
        return new self(
            kind: EntryKind::Request,
            parent: $folder,
            key: $requestId,
        );
    }

    public function __toString(): string
    {
        return "{$this->kind->value}|{$this->parent}|{$this->key}";
    }
}
