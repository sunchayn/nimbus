<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Workspace\ValueObjects;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Stringable;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;

/**
 * A folder of the workspace, as a path inside the `{workspace_root}/requests` directory.
 */
class FolderPath implements Stringable
{
    public const MAX_SEGMENT_LENGTH = 60;

    /**
     * @param  list<string>  $segments
     */
    private function __construct(
        private readonly array $segments,
    ) {}

    public static function root(): self
    {
        return new self([]);
    }

    /**
     * Builds a path from what a user typed.
     *
     * @throws WorkspaceException
     */
    public static function from(?string $folder): self
    {
        $segments = [];

        $normalizedPath = str_replace('\\', '/', (string) $folder);

        foreach (explode('/', $normalizedPath) as $segment) {
            // Dots and slashes never survive a slug, so the path cannot leave the requests directory.
            $slug = Str::limit(
                Str::slug($segment),
                self::MAX_SEGMENT_LENGTH,
                '',
            );

            if ($slug !== '') {
                $segments[] = $slug;
            }
        }

        if (count($segments) > SavedRequest::MAX_FOLDER_DEPTH) {
            throw WorkspaceException::folderTooDeep(SavedRequest::MAX_FOLDER_DEPTH);
        }

        return new self($segments);
    }

    /**
     * Builds a path from a value that was already stored, so its segments are kept as they are.
     */
    public static function fromStored(string $path): self
    {
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if (! in_array($segment, ['', '.'], true)) {
                $segments[] = $segment;
            }
        }

        return new self($segments);
    }

    public function isRoot(): bool
    {
        return $this->segments === [];
    }

    public function depth(): int
    {
        return count($this->segments);
    }

    /**
     * The last segment, which is empty for the root.
     */
    public function name(): string
    {
        return Arr::last($this->segments) ?? '';
    }

    public function parent(): self
    {
        return new self(segments: array_slice($this->segments, 0, -1));
    }

    /**
     * The first segment, which is empty for the root.
     */
    public function head(): string
    {
        return $this->segments[0] ?? '';
    }

    /**
     * The path without its first segment.
     */
    public function tail(): self
    {
        return new self(segments: array_slice($this->segments, 1));
    }

    public function join(string $name): self
    {
        return new self([...$this->segments, $name]);
    }

    public function equals(self $other): bool
    {
        return $this->segments === $other->segments;
    }

    public function __toString(): string
    {
        return implode('/', $this->segments);
    }
}
