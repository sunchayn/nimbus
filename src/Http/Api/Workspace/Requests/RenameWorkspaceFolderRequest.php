<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\Concerns\ResolvesFolderPath;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RenameFolderData;

class RenameWorkspaceFolderRequest extends FormRequest
{
    use ResolvesFolderPath;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folder' => $this->folderRules(presence: 'required'),
            'name' => $this->folderRules(
                presence: 'required',
                isNested: false,
            ),
        ];
    }

    public function toData(): RenameFolderData
    {
        return new RenameFolderData(
            folder: $this->folderPath(),
            name: $this->folderPath('name'),
        );
    }
}
