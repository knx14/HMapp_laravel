<?php

namespace App\Http\Requests\Api\V1\Farm;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認証は middleware 側で担保
    }

    public function rules(): array
    {
        return [
            'farm_name' => ['required', 'string', 'max:255'],
            'cultivation_method' => ['nullable', 'string', 'max:255'],
            'crop_type' => ['nullable', 'string', 'max:255'],
            'boundary_polygon' => ['required', 'array', 'min:4'],
            'boundary_polygon.*' => ['required', 'array'],
            'boundary_polygon.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'boundary_polygon.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}

