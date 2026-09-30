<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Web の圃場登録・編集。境界のルールはモバイル向け API（StoreFarmRequest）と同じで、境界の無い仮登録も許す。
 */
class SaveFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $boundary = $this->input('boundary_polygon');

        if (!is_string($boundary)) {
            return;
        }

        if (trim($boundary) === '') {
            $this->merge(['boundary_polygon' => null]);
            return;
        }

        $decoded = json_decode($boundary, true);
        if (is_array($decoded)) {
            $this->merge(['boundary_polygon' => $decoded === [] ? null : $decoded]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'farm_name' => ['required', 'string', 'max:255'],
            'cultivation_method' => ['nullable', 'string', 'max:255'],
            'crop_type' => ['nullable', 'string', 'max:255'],
            'boundary_polygon' => ['nullable', 'array', 'min:4'],
            'boundary_polygon.*' => ['required', 'array'],
            'boundary_polygon.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'boundary_polygon.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ];

        if ($this->user()->isAdmin()) {
            $rules['app_user_id'] = [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'exists:app_users,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'farm_name.required' => '圃場名を入力してください。',
            'farm_name.max' => '圃場名は255文字以内で入力してください。',
            'boundary_polygon.array' => '境界の形式が正しくありません。',
            'boundary_polygon.min' => '境界は4点以上で描いてください。境界を描かずに保存すると仮登録になります。',
            'boundary_polygon.*.lat.*' => '境界の座標が正しくありません。',
            'boundary_polygon.*.lng.*' => '境界の座標が正しくありません。',
            'app_user_id.required' => '所有者を選択してください。',
            'app_user_id.exists' => '選択した所有者が見つかりません。',
        ];
    }

    /**
     * @return list<array{lat: float, lng: float}>|null
     */
    public function boundary(): ?array
    {
        $points = $this->validated('boundary_polygon');

        return $points === null
            ? null
            : array_map(fn (array $p) => ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng']], array_values($points));
    }
}
