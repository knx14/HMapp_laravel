<?php

namespace App\Http\Requests\Api\V1\WorkLog;

use App\Enums\WorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_type' => ['sometimes', new Enum(WorkType::class)],
            'work_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'title' => ['sometimes', 'nullable', 'string', 'max:128'],
            'detail' => ['sometimes', 'nullable', 'string'],
            'amount_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'amount_unit' => ['sometimes', 'nullable', 'string', 'max:16'],
            'scope' => ['sometimes', 'in:whole,partial'],
        ];
    }
}
