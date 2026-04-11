<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:100'],
            'apellido'    => ['required', 'string', 'max:100'],
            'telefono'    => ['nullable', 'string', 'max:20'],
            // Ignorar el DUI del cliente actual al validar unique
            'dui'         => ['nullable', 'string', 'max:20', 'unique:clientes,dui,' . $this->cliente->id],
            'direccion'   => ['nullable', 'string'],
            'referencias' => ['nullable', 'string'],
            'estado'      => ['required', 'in:activo,inactivo,bloqueado'],
            'notas'       => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'dui.unique'        => 'Este DUI ya está registrado en otro cliente.',
            'estado.in'         => 'El estado seleccionado no es válido.',
        ];
    }
}