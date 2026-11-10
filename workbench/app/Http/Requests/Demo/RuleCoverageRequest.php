<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

class RuleCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|in:admin,member',
            'name' => 'required|string|between:2,40|alpha_dash',
            'nickname' => 'nullable|string|max:20',
            'company' => 'required_if:role,admin|string',
            'website' => 'sometimes|url',
            'server_ip' => 'nullable|ipv4',
            'birthday' => 'required|date_format:Y-m-d',
            'starts_at' => 'required|date_format:Y-m-d H:i',
            'slug' => 'required|regex:/^[a-z0-9-]+$/',
            'pin' => 'required|digits:4',
            'password' => 'required|string|min:8|confirmed',
            'internal_note' => 'prohibited',
            'tags' => 'required|array',
            'tags.*' => 'string|distinct',
            'author_id' => 'required|integer|exists:users,id',
            'email' => 'required|email|unique:users,email',
            'avatar' => 'nullable|image|mimes:jpg,png',
        ];
    }
}
