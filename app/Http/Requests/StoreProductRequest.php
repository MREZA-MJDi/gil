<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'base_price' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'unit' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(['active','inactive','unavailable'])],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'options' => ['sometimes', 'array', 'max:30'],
            'options.*.id' => ['sometimes', 'integer', 'distinct'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.type' => ['required', Rule::in(['size','addon'])],
            'options.*.price' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'options.*.is_active' => ['sometimes', 'boolean'],
            'options.*.sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
