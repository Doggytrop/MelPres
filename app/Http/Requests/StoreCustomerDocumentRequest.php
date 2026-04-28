<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tipo'    => ['required', 'in:profile_photo,id_front,id_back,address_proof,payroll,otro'],
            'archivo' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp'],
            'notas'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.max'      => 'El archivo no puede pesar más de 10MB.',
            'archivo.mimes'    => 'Solo se permiten imágenes (JPG, PNG) o PDF.',
            'tipo.required'    => 'Debes seleccionar el tipo de documento.',
        ];
    }
}