<?php

namespace Workbench\App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArrayOfPrimitivesRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'category_id' => 'required|integer',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'discontinued'])],
            'stock_quantity' => 'nullable|integer|min:0|max:999999',
            'sku' => 'nullable|uuid|max:50',
            'weight' => 'nullable|numeric|min:0|max:999.99',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'required_with:dimensions|numeric|min:0|max:999.99',
            'dimensions.width' => 'required_with:dimensions|numeric|min:0|max:999.99',
            'dimensions.height' => 'required_with:dimensions|numeric|min:0|max:999.99',
            'specifications' => 'nullable|array',
            'specifications.*' => 'string|max:15',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
            'is_featured' => 'nullable|boolean',
            'is_digital' => 'nullable|boolean',
            'download_url' => 'required|nullable|url|max:500',
            'seo' => 'nullable|array',
            'seo.title' => 'nullable|string|max:60',
            'seo.description' => 'nullable|string|max:160',
            'seo.keywords' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price.max' => 'Price cannot exceed 999,999.99.',
            'stock_quantity.max' => 'Stock quantity cannot exceed 999,999.',
            'sku.unique' => 'This SKU is already in use.',
            'weight.max' => 'Weight cannot exceed 999.99.',
            'dimensions.length.max' => 'Length cannot exceed 999.99.',
            'dimensions.width.max' => 'Width cannot exceed 999.99.',
            'dimensions.height.max' => 'Height cannot exceed 999.99.',
            'download_url.required_if' => 'Download URL is required for digital products.',
            'seo.title.max' => 'SEO title should not exceed 60 characters.',
            'seo.description.max' => 'SEO description should not exceed 160 characters.',
            'tags.max' => 'You can only have up to 20 tags.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'stock_quantity' => 'stock quantity',
            'dimensions.length' => 'length',
            'dimensions.width' => 'width',
            'dimensions.height' => 'height',
            'is_featured' => 'featured status',
            'is_digital' => 'digital product status',
            'download_url' => 'download URL',
            'seo.title' => 'SEO title',
            'seo.description' => 'SEO description',
            'seo.keywords' => 'SEO keywords',
        ];
    }
}
