<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone'      => ['nullable', 'string', 'max:20'],
            'address'    => ['nullable', 'string'],
            'references' => ['nullable', 'string'],
            'status'     => ['required', 'in:active,inactive,blocked'],
            'notes'      => ['nullable', 'string'],
            'latitude'  => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in'       => 'El estado seleccionado no es válido.',
        ];
    }
}