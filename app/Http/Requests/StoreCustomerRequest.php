<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'phone'           => ['nullable', 'string', 'max:20', 'unique:customers,phone'],
            'document_type'   => ['nullable', 'in:ine,passport,license,id_card,other'],
            'document_number' => ['nullable', 'string', 'max:50', 'unique:customers,document_number'],
            'address'         => ['nullable', 'string', 'max:500'],
            'references'      => ['nullable', 'string', 'max:500'],
            'status'          => ['required', 'in:active,inactive,blocked'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'       => 'El nombre es obligatorio.',
            'first_name.max'            => 'El nombre no puede superar 100 caracteres.',
            'last_name.required'        => 'El apellido es obligatorio.',
            'last_name.max'             => 'El apellido no puede superar 100 caracteres.',
            'phone.unique'              => 'Este número de teléfono ya está registrado.',
            'phone.max'                 => 'El teléfono no puede superar 20 caracteres.',
            'document_type.in'          => 'El tipo de documento no es válido.',
            'document_number.unique'    => 'Este número de documento ya está registrado.',
            'document_number.max'       => 'El número de documento no puede superar 50 caracteres.',
            'status.required'           => 'El estado es obligatorio.',
            'status.in'                 => 'El estado seleccionado no es válido.',
            'latitude.between'          => 'La latitud no es válida.',
            'longitude.between'         => 'La longitud no es válida.',
        ];
    }
}