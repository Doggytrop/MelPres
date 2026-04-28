<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount_paid'   => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'observaciones'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_paid.required' => 'El monto del payment es obligatorio.',
            'amount_paid.min'      => 'El monto debe ser mayor a 0.',
            'payment_date.required'   => 'La fecha del payment es obligatoria.',
        ];
    }
}