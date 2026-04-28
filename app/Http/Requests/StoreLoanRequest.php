<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'      => ['required', 'exists:customers,id'],
            'type'             => ['required', 'in:interest,term'],
            'payment_frequency'=> ['required', 'in:weekly,biweekly,monthly'],
            'original_amount'  => ['required', 'numeric', 'min:1'],
            'interest_rate'    => ['required', 'numeric', 'min:0', 'max:100'],
            'number_of_periods'=> ['nullable', 'integer', 'min:1'],
            'penalty_type'     => ['nullable', 'in:fixed,percentage'],
            'penalty_value'    => ['nullable', 'numeric', 'min:0'],
            'grace_days'       => ['nullable', 'integer', 'min:0'],
            'start_date'       => ['required', 'date'],
            'notes'            => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'     => 'You must select a customer.',
            'customer_id.exists'       => 'The selected customer does not exist.',
            'original_amount.required' => 'Amount is required.',
            'original_amount.min'      => 'Amount must be greater than 0.',
            'interest_rate.required'   => 'Interest rate is required.',
            'start_date.required'      => 'Start date is required.',
        ];
    }
}