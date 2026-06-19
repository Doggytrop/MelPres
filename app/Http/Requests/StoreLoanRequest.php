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
            'original_amount'   => ['required', 'numeric', 'min:1', 'max:9999999'],
            'interest_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'number_of_periods' => ['nullable', 'integer', 'min:1', 'max:365'],
            'penalty_type'      => ['nullable', 'in:fixed,percentage_period,percentage_balance'],
            'penalty_value'     => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'grace_days'        => ['nullable', 'integer', 'min:0', 'max:30'],
            'start_date'        => ['required', 'date'],
            'due_date'          => ['nullable', 'date', 'after:start_date'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'     => 'Debes seleccionar un cliente.',
            'customer_id.exists'       => 'El cliente seleccionado no existe.',
            'type.required'            => 'El tipo de préstamo es obligatorio.',
            'type.in'                  => 'El tipo de préstamo no es válido.',
            'original_amount.required' => 'El monto es obligatorio.',
            'original_amount.min'      => 'El monto debe ser mayor a 0.',
            'original_amount.max'      => 'El monto excede el límite permitido.',
            'interest_rate.required'   => 'La tasa de interés es obligatoria.',
            'interest_rate.min'        => 'La tasa no puede ser negativa.',
            'interest_rate.max'        => 'La tasa no puede superar el 100%.',
            'number_of_periods.min'    => 'El número de periodos debe ser al menos 1.',
            'number_of_periods.max'    => 'El número de periodos no puede superar 365.',
            'penalty_type.in'          => 'El tipo de mora no es válido.',
            'penalty_value.min'        => 'El valor de mora no puede ser negativo.',
            'grace_days.min'           => 'Los días de gracia no pueden ser negativos.',
            'grace_days.max'           => 'Los días de gracia no pueden superar 30.',
            'start_date.required'      => 'La fecha de inicio es obligatoria.',
            'due_date.after'           => 'La fecha de vencimiento debe ser posterior a la de inicio.',
            'notes.max'                => 'Las notas no pueden superar 1000 caracteres.',
        ];
    }
}