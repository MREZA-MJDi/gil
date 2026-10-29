<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $decodedFields = [];
        foreach (['options', 'allergens', 'dietary_tags'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $decodedFields[$field] = $decoded;
                }
            }
        }
        if ($decodedFields !== []) $this->merge($decodedFields);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'ingredients' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'allergens' => ['sometimes', 'nullable', 'array', 'max:30'],
            'allergens.*' => ['string', 'max:80'],
            'dietary_tags' => ['sometimes', 'nullable', 'array', 'max:30'],
            'dietary_tags.*' => ['string', 'max:80'],
            'calories' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10000'],
            'preparation_time_minutes' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1440'],
            'base_price' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000000000000'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'required', Rule::in(['active','inactive','unavailable'])],
            'sort_order' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000000'],
            'stock_quantity' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000000000'],
            'low_stock_threshold' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000000000'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'options' => ['sometimes', 'array', 'max:30'],
            'options.*.id' => ['sometimes', 'integer', 'distinct'],
            'options.*.name' => ['required_with:options', 'string', 'max:100'],
            'options.*.type' => ['required_with:options', Rule::in(['size','addon'])],
            'options.*.price' => ['required_with:options', 'integer', 'min:0', 'max:1000000000000'],
            'options.*.is_active' => ['sometimes', 'boolean'],
            'options.*.sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
