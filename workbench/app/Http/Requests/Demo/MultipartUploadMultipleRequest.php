<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

class MultipartUploadMultipleRequest extends FormRequest
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
            'files.*' => 'file|max:2048',
        ];
    }
}
