<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

class JsonUploadMultipleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'files' => 'required|array|min:1|max:3',
            'files.*.name' => 'required|string|max:255',
            'files.*.content' => 'required|string',
            'files.*.mime_type' => 'nullable|string|max:100',
        ];
    }
}
