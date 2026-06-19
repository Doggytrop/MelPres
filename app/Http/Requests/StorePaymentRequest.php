<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $loan = $this->route('loan');
        $maxAmount = $loan
            ? floatval($loan->remaining_balance) + floatval($loan->accumulated_penalty) + floatval($loan->pending_interest)
            : 9999999;

        return [
            'amount_paid'   => ['required', 'numeric', 'min:0.01', 'max:' . $maxAmount],
            'payment_date'  => ['required', 'date', 'before_or_equal:today'],
            'expected_date' => ['nullable', 'date'],
            'notes'         => ['nullable', 'string', 'max:500'],
            'periods'       => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_paid.required'      => 'El monto del pago es obligatorio.',
            'amount_paid.min'           => 'El monto debe ser mayor a 0.',
            'amount_paid.max'           => 'El monto no puede superar el saldo total del préstamo.',
            'payment_date.required'     => 'La fecha del pago es obligatoria.',
            'payment_date.before_or_equal' => 'La fecha del pago no puede ser en el futuro.',
            'notes.max'                 => 'Las notas no pueden superar 500 caracteres.',
            'periods.min'               => 'El número de periodos debe ser al menos 1.',
            'periods.max'               => 'El número de periodos no puede superar 365.',
        ];
    }
}