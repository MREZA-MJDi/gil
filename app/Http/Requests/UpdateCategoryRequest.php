<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $category = $this->route('category');
        $id = is_object($category) ? $category->id : $category;
        return [
            'name' => ['sometimes','required','string','max:100',Rule::unique('categories','name')->ignore($id)],
            'sort_order' => ['sometimes','required','integer','min:0','max:1000000'],
            'is_active' => ['sometimes','required','boolean'],
        ];
    }
}
