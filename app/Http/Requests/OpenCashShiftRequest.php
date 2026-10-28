<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashShiftRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['opening_cash' => ['required','integer','min:0','max:1000000000000'], 'note' => ['nullable','string','max:500']]; }
}
