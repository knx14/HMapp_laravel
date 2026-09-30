<?php

namespace App\Http\Requests\Api\V1;

use App\Support\OrganizationName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認証は middleware 側で担保
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('organization')) {
            $value = $this->input('organization');
            $this->merge(['organization' => is_string($value) ? OrganizationName::normalize($value) : $value]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'ja_name' => ['nullable', 'string', 'max:255'],
            // 古いモバイル版は送らないので、送られたときだけ必須にする
            'organization' => ['sometimes', ...OrganizationName::rules()],
        ];
    }

    public function messages(): array
    {
        return OrganizationName::messages();
    }
}
