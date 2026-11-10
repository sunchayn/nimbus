<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RuleObjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => str($this->input('title'))->slug()->toString()]);
    }

    public function attributes(): array
    {
        return [
            'title' => 'Post title',
        ];
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'slug' => 'required|string',
            'role' => ['required', Rule::in(['admin', 'member'])],
            'company' => [Rule::requiredIf(true), 'string'],
            'discount' => [Rule::prohibitedIf(true)],
            'phone' => [Rule::when(true, ['required', 'string'], ['nullable'])],
            'author_id' => ['required', Rule::exists('users', 'id')],
            'email' => ['required', Rule::unique('users', 'email')],
            'banner' => ['nullable', Rule::dimensions()->minWidth(100)],
            'code' => ['required', function (string $attribute, mixed $value, \Closure $fail): void {}],
        ];
    }
}
