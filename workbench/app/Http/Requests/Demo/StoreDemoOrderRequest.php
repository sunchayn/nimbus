<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemoOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'payment_method' => ['required', Rule::in(['credit_card', 'paypal', 'bank_transfer'])],
            'shipping_address' => ['required', 'array'],
            'shipping_address.street' => ['required', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
