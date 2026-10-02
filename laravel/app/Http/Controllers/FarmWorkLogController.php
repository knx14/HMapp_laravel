<?php

namespace App\Http\Controllers;

use App\Enums\WorkType;
use App\Models\Farm;
use App\Models\WorkLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FarmWorkLogController extends Controller
{
    public function store(Request $request, Farm $farm): RedirectResponse
    {
        Gate::authorize('manage', $farm);

        $farm->workLogs()->create($this->validated($request));

        return $this->backToFarm($farm, $request)->with('success', '作業記録を追加しました。');
    }

    public function update(Request $request, Farm $farm, WorkLog $workLog): RedirectResponse
    {
        Gate::authorize('manage', $farm);
        abort_unless((int) $workLog->farm_id === (int) $farm->id, 404);

        $workLog->update($this->validated($request));

        return $this->backToFarm($farm, $request)->with('success', '作業記録を保存しました。');
    }

    public function destroy(Request $request, Farm $farm, WorkLog $workLog): RedirectResponse
    {
        Gate::authorize('manage', $farm);
        abort_unless((int) $workLog->farm_id === (int) $farm->id, 404);

        $workLog->delete();

        return $this->backToFarm($farm, $request)->with('success', '作業記録を削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'work_type' => ['required', Rule::enum(WorkType::class)],
            'work_date' => ['required', 'date', 'date_format:Y-m-d'],
            'title' => ['nullable', 'string', 'max:128'],
            'detail' => ['nullable', 'string'],
            'amount_value' => ['nullable', 'numeric', 'min:0'],
            'amount_unit' => ['nullable', 'string', 'max:16'],
        ], [], [
            'work_type' => '作業種別',
            'work_date' => '作業日',
            'title' => 'タイトル',
            'detail' => 'メモ',
            'amount_value' => '量',
            'amount_unit' => '単位',
        ]);
    }

    private function backToFarm(Farm $farm, Request $request): RedirectResponse
    {
        $date = $request->input('return_date');
        $parameters = ['farm' => $farm];
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $parameters['date'] = $date;
        }

        return redirect()->to(route('farm-management.show', $parameters).'#timeline');
    }
}
