<?php

namespace App\Http\Requests\Api\V1\WorkLog;

use App\Enums\WorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_type' => ['required', new Enum(WorkType::class)],
            'work_date' => ['required', 'date', 'date_format:Y-m-d'],
            'title' => ['nullable', 'string', 'max:128'],
            'detail' => ['nullable', 'string'],
            'amount_value' => ['nullable', 'numeric', 'min:0'],
            'amount_unit' => ['nullable', 'string', 'max:16'],
            'scope' => ['nullable', 'in:whole,partial'],
        ];
    }
}
