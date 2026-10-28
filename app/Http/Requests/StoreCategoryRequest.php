<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:100','unique:categories,name'],
            'sort_order' => ['sometimes','integer','min:0','max:1000000'],
            'is_active' => ['sometimes','boolean'],
        ];
    }
}
