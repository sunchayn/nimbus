<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

class MultipartUploadMixedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'file' => 'required|file|max:2048',
            'attachments' => 'sometimes|array|max:3',
            'attachments.*' => 'file|max:2048',
            'meta' => 'sometimes|array',
            'meta.description' => 'nullable|string|max:255',
        ];
    }
}
