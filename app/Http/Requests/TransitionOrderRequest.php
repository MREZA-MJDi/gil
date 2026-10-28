<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['confirmed', 'preparing', 'ready', 'delivered'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
