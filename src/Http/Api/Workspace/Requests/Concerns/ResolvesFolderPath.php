<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Requests\Concerns;

use Sunchayn\Nimbus\Http\Api\Workspace\Rules\WorkspaceFolderRule;
use Sunchayn\Nimbus\Modules\Workspace\Exceptions\WorkspaceException;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

/**
 * Validates the folder fields of a form request and converts them to `FolderPath`.
 */
trait ResolvesFolderPath
{
    /**
     * @return list<mixed>
     */
    protected function folderRules(
        string $presence = 'nullable',
        bool $isNested = true,
    ): array {
        return [
            $presence,
            'string',
            'max:300',
            new WorkspaceFolderRule($isNested),
        ];
    }

    /**
     * @throws WorkspaceException
     */
    public function folderPath(string $key = 'folder'): FolderPath
    {
        return FolderPath::from($this->validated($key));
    }

    /**
     * @return list<FolderPath>
     *
     * @throws WorkspaceException
     */
    public function folderPaths(string $key): array
    {
        /** @var list<string> $folders */
        $folders = array_values($this->validated($key));

        return array_map(FolderPath::from(...), $folders);
    }
}
