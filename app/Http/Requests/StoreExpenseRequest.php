<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'title' => ['required','string','max:150'],
            'category' => ['required',Rule::in(['rent','payroll','utilities','ingredients','repairs','packaging','transport','other'])],
            'amount' => ['required','integer','min:1','max:1000000000000'],
            'payment_method' => ['required',Rule::in(['cash','card_terminal','online','other'])],
            'is_paid' => ['sometimes','boolean'],
            'receipt_path' => ['nullable','string','max:255'],
            'note' => ['nullable','string','max:1000'],
        ];
    }
}
