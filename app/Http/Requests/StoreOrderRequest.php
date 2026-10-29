<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->input('channel', 'dine_in'),
            'discount' => (int) $this->input('discount', 0),
            'idempotency_key' => trim((string) $this->header('Idempotency-Key', $this->input('idempotency_key'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(['dine_in', 'takeaway', 'online', 'counter'])],
            'restaurant_table_id' => ['nullable', 'integer', 'exists:restaurant_tables,id'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.options' => ['sometimes', 'array', 'max:20'],
            'items.*.options.*.id' => ['required', 'integer', 'distinct', 'exists:product_options,id'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'discount' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ];
    }
}
