<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'monto_pagado'   => ['required', 'numeric', 'min:0.01'],
            'fecha_pago'     => ['required', 'date'],
            'fecha_esperada' => ['nullable', 'date'],
            'observaciones'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_pagado.required' => 'El monto del pago es obligatorio.',
            'monto_pagado.min'      => 'El monto debe ser mayor a 0.',
            'fecha_pago.required'   => 'La fecha del pago es obligatoria.',
        ];
    }
}