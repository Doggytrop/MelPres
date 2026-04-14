<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrestamoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cliente_id'       => ['required', 'exists:clientes,id'],
            'tipo'             => ['required', 'in:interes,plazo'],
            'frecuencia_pago'  => ['required', 'in:semanal,quincenal,mensual'],
            'monto_original'   => ['required', 'numeric', 'min:1'],
            'interes_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'numero_periodos'  => ['nullable', 'integer', 'min:1'],
            'mora_tipo'        => ['nullable', 'in:fija,porcentaje'],
            'mora_valor'       => ['nullable', 'numeric', 'min:0'],
            'dias_gracia'      => ['nullable', 'integer', 'min:0'],
            'fecha_inicio'     => ['required', 'date'],
            'observaciones'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required'      => 'Debes seleccionar un cliente.',
            'cliente_id.exists'        => 'El cliente seleccionado no existe.',
            'monto_original.required'  => 'El monto es obligatorio.',
            'monto_original.min'       => 'El monto debe ser mayor a 0.',
            'interes_rate.required'    => 'El interés es obligatorio.',
            'fecha_inicio.required'    => 'La fecha de inicio es obligatoria.',
        ];
    }
}