<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => trim((string) $this->header('Idempotency-Key', $this->input('idempotency_key')))]);
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ];
    }
}
