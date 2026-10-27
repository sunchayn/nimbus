<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The rules need a logged in user, so extraction fails and falls back to the array shape.
 */
class UnresolvableRulesRequest extends FormRequest
{
    public function rules(): array
    {
        $maxLength = $this->user()->role === 'admin' ? 500 : 100;

        return [
            'title' => ['required', 'string', 'max:'.$maxLength],
            'body' => ['required', 'string'],
            'published' => ['sometimes', 'boolean'],
        ];
    }
}
