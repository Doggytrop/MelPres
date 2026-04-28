<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
        {
            return [
                'first_name'      => ['required', 'string', 'max:100'],
                'last_name'       => ['required', 'string', 'max:100'],
                'phone'           => ['nullable', 'string', 'max:20'],
                'document_type'   => ['nullable', 'in:ine,passport,license,id_card,other'],
                'document_number' => ['nullable', 'string', 'max:50', 'unique:customers,document_number,' . $this->customer->id],
                'address'         => ['nullable', 'string'],
                'references'      => ['nullable', 'string'],
                'status'          => ['required', 'in:active,inactive,blocked'],
                'notes'           => ['nullable', 'string'],
            ];
        }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'dui.unique'        => 'Este DUI ya está registrado en otro customer.',
            'status.in'         => 'El status seleccionado no es válido.',
        ];
    }
}