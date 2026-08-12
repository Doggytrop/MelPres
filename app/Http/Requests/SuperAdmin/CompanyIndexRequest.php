<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'subscription_status' => [
                'nullable',
                Rule::enum(SubscriptionStatus::class),
            ],
        ];
    }
}
