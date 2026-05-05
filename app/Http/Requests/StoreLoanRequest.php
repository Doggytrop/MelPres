<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'       => ['required', 'exists:customers,id'],
            'type'              => ['required', 'in:interest,term,daily'],
            'payment_frequency' => ['nullable', 'in:weekly,biweekly,monthly,daily'],
            'original_amount'   => ['required', 'numeric', 'min:1'],
            'interest_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'number_of_periods' => ['nullable', 'integer', 'min:1'],
            'penalty_type'      => ['nullable', 'in:fixed,percentage'],
            'penalty_value'     => ['nullable', 'numeric', 'min:0'],
            'grace_days'        => ['nullable', 'integer', 'min:0'],
            'start_date'        => ['required', 'date'],
            'notes'             => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'     => 'Debes seleccionar un cliente.',
            'customer_id.exists'       => 'El cliente seleccionado no existe.',
            'original_amount.required' => 'El monto es obligatorio.',
            'original_amount.min'      => 'El monto debe ser mayor a 0.',
            'interest_rate.required'   => 'El interés es obligatorio.',
            'start_date.required'      => 'La fecha de inicio es obligatoria.',
        ];
    }
}