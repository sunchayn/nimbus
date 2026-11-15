<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\Concerns\ResolvesFolderPath;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

class MoveWorkspaceRequestRequest extends FormRequest
{
    use ResolvesFolderPath;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folder' => $this->folderRules(),
            'name' => ['nullable', 'string', 'min:1', 'max:120'],
            'hash' => ['required', 'string', 'max:64'],
        ];
    }

    public function requestId(): string
    {
        return (string) $this->route('id');
    }

    public function newName(): ?string
    {
        return $this->validated('name');
    }

    public function hash(): string
    {
        return $this->validated('hash');
    }

    public function requestedFolder(): ?FolderPath
    {
        // An empty folder is the root, but the request turns it into null.
        // Null means the request keeps its current folder.
        if (! array_key_exists('folder', $this->validated())) {
            return null;
        }

        return $this->folderPath();
    }
}
