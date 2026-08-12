<?php

namespace App\Http\Requests\SuperAdmin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateCompanyGraceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'grace_until' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $renewal = $this->route('company')?->subscription?->current_period_end;

                    if ($value && $renewal && Carbon::parse($value)->lt($renewal)) {
                        $fail('El periodo de gracia debe iniciar al vencer el periodo contratado.');
                    }
                },
            ],
        ];
    }
}
