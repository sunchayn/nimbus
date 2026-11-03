<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

class JsonUploadSingleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'file' => 'required|array',
            'file.name' => 'required|string|max:255',
            'file.content' => 'required|string',
            'file.mime_type' => 'nullable|string|max:100',
        ];
    }
}
