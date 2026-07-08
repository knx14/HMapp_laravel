<?php

namespace App\Http\Requests\Api;

use App\Support\SoilParameterUnits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreManualResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'measurement_date' => ['required', 'date', 'date_format:Y-m-d'],
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['nullable', 'numeric'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $values = $this->input('values', []);
            if (!is_array($values)) {
                return;
            }

            $hasValue = false;
            foreach ($values as $name => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $hasValue = true;
                if (!SoilParameterUnits::isAllowed((string) $name)) {
                    $validator->errors()->add(
                        "values.{$name}",
                        "Unsupported parameter: {$name}"
                    );
                }
            }

            if (!$hasValue) {
                $validator->errors()->add('values', 'At least one parameter value is required.');
            }
        });
    }
}
