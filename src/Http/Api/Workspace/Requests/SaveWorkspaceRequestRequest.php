<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Workspace\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sunchayn\Nimbus\Http\Api\Workspace\Requests\Concerns\ResolvesFolderPath;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;

class SaveWorkspaceRequestRequest extends FormRequest
{
    use ResolvesFolderPath;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'method' => ['required', 'string', Rule::in(SavedRequest::METHODS)],
            'endpoint' => ['required', 'string', 'max:2048'],
            'route' => ['nullable', 'string', 'max:255'],
            'headers' => ['present', 'array', 'max:200'],
            'headers.*.key' => ['present', 'nullable', 'string', 'max:255'],
            'headers.*.value' => ['present', 'nullable', 'string', 'max:8192'],
            'headers.*.enabled' => ['required', 'boolean'],
            'query' => ['present', 'array', 'max:200'],
            'query.*.key' => ['present', 'nullable', 'string', 'max:255'],
            'query.*.value' => ['present', 'nullable', 'string', 'max:8192'],
            'query.*.enabled' => ['required', 'boolean'],
            'body' => ['required', 'array'],
            'body.type' => ['required', 'string', Rule::in(SavedRequest::BODY_TYPES)],
            'body.content' => ['nullable', 'string', 'max:524288'],
            'authorization' => ['required', 'array'],
            'authorization.type' => ['required', Rule::enum(AuthorizationTypeEnum::class)],
            'authorization.value' => ['nullable'],
            'hash' => ['nullable', 'string', 'max:64'],
            'folder' => $this->folderRules(),
        ];
    }

    public function hash(): ?string
    {
        return $this->validated('hash');
    }

    public function savedRequest(): SavedRequest
    {
        return SavedRequest::fromArray([
            ...$this->validated(),
            'id' => $this->route('id'),
        ]);
    }
}
