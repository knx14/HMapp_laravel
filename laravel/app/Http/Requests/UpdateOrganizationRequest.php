<?php

namespace App\Http\Requests;

use App\Support\OrganizationName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization' => OrganizationName::normalize(is_string($this->input('organization')) ? $this->input('organization') : null),
        ]);
    }

    public function rules(): array
    {
        return [
            'organization' => OrganizationName::rules(),
            'return_to' => ['nullable', 'in:profile'],
        ];
    }

    public function messages(): array
    {
        return OrganizationName::messages();
    }
}
