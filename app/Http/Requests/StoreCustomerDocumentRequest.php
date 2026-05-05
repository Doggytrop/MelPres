<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'  => ['required', 'in:profile_photo,id_front,id_back,address_proof,payroll,other'],
            'file'  => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.max'      => 'El archivo no puede pesar más de 10MB.',
            'file.mimes'    => 'Solo se permiten imágenes (JPG, PNG) o PDF.',
            'type.required' => 'Debes seleccionar el tipo de documento.',
        ];
    }
}