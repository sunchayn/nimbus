<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NestedObjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer',
            'ip' => 'required|ipv4',
            'version' => 'required|string',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'required|numeric|min:0|max:999999.99',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.street' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'required|string|max:100',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.country_geocode' => 'required|string|size:2',
            'billing_address' => 'sometimes|array',
            'billing_address.name' => 'required|string|max:255',
            'billing_address.street' => 'required|string|max:255',
            'billing_address.city' => 'required|string|max:100',
            'billing_address.state' => 'required|string|max:100',
            'billing_address.postal_code' => 'required|string|max:20',
            'billing_address.country_geocode' => 'required|string|size:2',
            'payment_method' => ['required', Rule::in(['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'cash_on_delivery'])],
            'payment_details' => 'nullable|array',
            'payment_details.card_number' => 'required|string|max:19',
            'payment_details.expiry_month' => 'required|integer|min:1|max:12',
            'payment_details.expiry_year' => 'required|integer|min:'.date('Y').'|max:'.(date('Y') + 10),
            'payment_details.cvv' => 'required|string|size:3',
            'shipping_method' => ['required', Rule::in(['standard', 'express', 'overnight', 'pickup'])],
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'gift_message' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'items.min' => 'Order must contain at least one item.',
            'items.max' => 'Order cannot contain more than 50 items.',
            'items.*.quantity.max' => 'Item quantity cannot exceed 999.',
            'items.*.price.max' => 'Item price cannot exceed 999,999.99.',
            'shipping_address.country.size' => 'Country code must be exactly 2 characters.',
            'billing_address.country.size' => 'Country code must be exactly 2 characters.',
            'payment_details.card_number.size' => 'Card number must be exactly 16 digits.',
            'payment_details.cvv.size' => 'CVV must be exactly 3 digits.',
            'payment_details.expiry_year.min' => 'Card expiry year cannot be in the past.',
            'payment_details.expiry_year.max' => 'Card expiry year cannot be more than 10 years in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'customer',
            'items.*.product_id' => 'product',
            'items.*.quantity' => 'quantity',
            'items.*.price' => 'price',
            'shipping_address.name' => 'shipping name',
            'shipping_address.street' => 'shipping street',
            'shipping_address.city' => 'shipping city',
            'shipping_address.state' => 'shipping state',
            'shipping_address.postal_code' => 'shipping postal code',
            'shipping_address.country' => 'shipping country',
            'billing_address.name' => 'billing name',
            'billing_address.street' => 'billing street',
            'billing_address.city' => 'billing city',
            'billing_address.state' => 'billing state',
            'billing_address.postal_code' => 'billing postal code',
            'billing_address.country' => 'billing country',
            'payment_method' => 'payment method',
            'payment_details.card_number' => 'card number',
            'payment_details.expiry_month' => 'expiry month',
            'payment_details.expiry_year' => 'expiry year',
            'payment_details.cvv' => 'CVV',
            'shipping_method' => 'shipping method',
            'coupon_code' => 'coupon code',
            'gift_message' => 'gift message',
        ];
    }
}
