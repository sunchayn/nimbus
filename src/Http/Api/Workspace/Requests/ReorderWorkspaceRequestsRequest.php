<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\Concerns\ResolvesFolderPath;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\ReorderRequestsData;

class ReorderWorkspaceRequestsRequest extends FormRequest
{
    use ResolvesFolderPath;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folder' => $this->folderRules(),
            'requests' => ['required', 'array', 'max:1000'],
            'requests.*' => ['string', 'max:100'],
        ];
    }

    public function toData(): ReorderRequestsData
    {
        /** @var list<string> $ids */
        $ids = array_values($this->validated('requests'));

        return new ReorderRequestsData(
            folder: $this->folderPath(),
            ids: $ids,
        );
    }
}
