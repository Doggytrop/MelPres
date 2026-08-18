<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount_paid'   => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'payment_date'  => ['required', 'date', 'before_or_equal:today'],
            'expected_date' => ['nullable', 'date'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_paid.required'      => 'El monto del pago es obligatorio.',
            'amount_paid.min'           => 'El monto no puede ser negativo.',
            'amount_paid.max'           => 'El monto no puede superar el saldo total del préstamo.',
            'payment_date.required'     => 'La fecha del pago es obligatoria.',
            'payment_date.before_or_equal' => 'La fecha del pago no puede ser en el futuro.',
            'notes.max'                 => 'Las notas no pueden superar 500 caracteres.',
        ];
    }
}
