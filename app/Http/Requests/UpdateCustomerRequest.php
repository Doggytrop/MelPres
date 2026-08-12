<?php

namespace App\Http\Requests;

use App\Services\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $company = app(CompanyContext::class)->getCompany();

        if (! $company || $company->status !== 'active') {
            abort(403, 'No hay una empresa activa asociada al usuario autenticado.');
        }

        $customer = $this->route('customer');

        if (! $customer || (int) $customer->company_id !== (int) $company->id) {
            abort(403, 'El cliente no pertenece a la empresa activa.');
        }

        $phoneUnique = Rule::unique('customers', 'phone')
            ->withoutTrashed()
            ->where(fn ($query) => $query->where('customers.company_id', $company->id))
            ->ignore($customer->getKey());

        $documentNumberUnique = Rule::unique('customers', 'document_number')
            ->withoutTrashed()
            ->where(fn ($query) => $query->where('customers.company_id', $company->id))
            ->ignore($customer->getKey());

        return [
            'phone'           => ['nullable', 'string', 'max:20', $phoneUnique],
            'document_number' => ['nullable', 'string', 'max:50', $documentNumberUnique],
            'address'         => ['nullable', 'string'],
            'references'      => ['nullable', 'string'],
            'status'          => ['required', 'in:active,inactive,blocked'],
            'notes'           => ['nullable', 'string'],
            'latitude'        => ['nullable', 'numeric'],
            'longitude'       => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique'           => 'Este número de teléfono ya está registrado.',
            'document_number.unique' => 'Este número de documento ya está registrado.',
            'status.required'        => 'El estado es obligatorio.',
            'status.in'              => 'El estado seleccionado no es válido.',
        ];
    }
}
