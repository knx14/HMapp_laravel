<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Upload;
use App\Services\Measurements\MeasurementCsvExporter;
use App\Services\Measurements\MeasurementDeleter;
use App\Services\Measurements\MeasurementDetail;
use App\Services\Measurements\MeasurementEditor;
use App\Services\Measurements\MeasurementFilters;
use App\Services\Measurements\MeasurementQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeasurementController extends Controller
{
    public function __construct(
        private MeasurementQuery $measurements,
        private MeasurementCsvExporter $exporter,
        private MeasurementDetail $detail,
        private MeasurementEditor $editor,
        private MeasurementDeleter $deleter,
    ) {}

    /**
     * GET /measurements
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = MeasurementFilters::fromRequest($request, $user);

        $uploads = $this->measurements->build($user, $filters)
            ->paginate(config('measurements.per_page'))
            ->appends($filters->toQuery());

        return view('measurements.index', [
            'uploads' => $uploads,
            'filters' => $filters,
            'isAdmin' => $user->isAdmin(),
            'trashedOptions' => MeasurementFilters::TRASHED_OPTIONS,
            'pendingUploads' => $user->isAdmin() ? $this->pendingUploads() : collect(),
        ]);
    }

    /**
     * POST /measurements/export
     * scope=selected は upload_ids[]、scope=all は絞り込み条件に一致する全件を出力する。
     */
    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', Rule::in(['selected', 'all'])],
            'upload_ids' => ['exclude_unless:scope,selected', 'required', 'array'],
            'upload_ids.*' => ['integer'],
        ], [
            'upload_ids.required' => 'ダウンロードする行を選択してください。',
        ]);

        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $filters = MeasurementFilters::fromRequest($request, $user);

        $query = $validated['scope'] === 'selected'
            ? $this->measurements->build(
                $user,
                new MeasurementFilters(trashed: $isAdmin ? MeasurementFilters::TRASHED_WITH : MeasurementFilters::TRASHED_WITHOUT),
                array_map('intval', $validated['upload_ids']),
            )
            : $this->measurements->build($user, $filters);

        if ($isAdmin) {
            $limit = (int) config('measurements.admin_export_limit');
            $count = (clone $query)->reorder()->count();

            if ($count > $limit) {
                return redirect()
                    ->route('measurements.index', $filters->toQuery())
                    ->with('error', "生データ付きの CSV は1回{$limit}件までです（対象 {$count} 件）。条件を絞ってください。");
            }
        }

        $fileName = 'measurements_'.now(config('measurements.display_timezone'))->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query, $isAdmin): void {
            $out = fopen('php://output', 'w');
            try {
                $this->exporter->write($out, $query, $isAdmin);
            } finally {
                fclose($out);
            }
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * GET /measurements/{upload}
     * 詳細ポップアップの内容。管理者は論理削除済みの測定も開ける。
     */
    public function show(Request $request, int $upload): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => $this->detail->present($this->findUpload($user, $upload), $user)]);
    }

    /**
     * PATCH /measurements/{upload}/location
     */
    public function updateLocation(Request $request, int $upload): JsonResponse
    {
        $user = $request->user();
        $model = $this->findUpload($user, $upload);
        $this->ensureNotTrashed($model);
        Gate::authorize('update', $model);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $point = $model->analysisResult;
        abort_if($point === null, 404);

        $point->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json(['data' => $this->detail->present($model->fresh(), $user)]);
    }

    /**
     * PUT /measurements/{upload}
     * 管理者による推定値・測定日時・測定番号の編集。
     */
    public function update(Request $request, int $upload): JsonResponse
    {
        $user = $request->user();
        $model = $this->findUpload($user, $upload);
        $this->ensureNotTrashed($model);
        Gate::authorize('edit', $model);

        $validated = $request->validate([
            'measurement_date' => ['required', 'date_format:Y-m-d'],
            'measurement_time' => ['nullable', 'date_format:H:i'],
            'measurement_number' => ['nullable', 'integer', 'min:1'],
            'values' => ['sometimes', 'array'],
            'values.*' => ['nullable', 'numeric'],
        ], [
            'measurement_date.required' => '測定日を入力してください。',
            'measurement_date.date_format' => '測定日は YYYY-MM-DD の形式で入力してください。',
            'measurement_time.date_format' => '時刻は HH:MM の形式で入力してください。',
            'measurement_number.integer' => '測定番号は1以上の整数で入力してください。',
            'measurement_number.min' => '測定番号は1以上の整数で入力してください。',
            'values.*.numeric' => '推定値は数値で入力してください。',
        ]);

        $validated['values'] = array_intersect_key($validated['values'] ?? [], array_flip(MeasurementDetail::PARAMETERS));

        $this->editor->update($model, $validated);

        return response()->json(['data' => $this->detail->present($model->fresh(), $user)]);
    }

    /**
     * POST /measurements/delete
     * チェックした測定を論理削除する。権限の無い ID と削除済みの ID は無視する。
     */
    public function destroySelected(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'upload_ids' => ['required', 'array'],
            'upload_ids.*' => ['integer'],
        ], [
            'upload_ids.required' => '削除する行を選択してください。',
        ]);

        $user = $request->user();
        $deleted = $this->deleter->deleteMany($user, array_map('intval', $validated['upload_ids']));

        return redirect()
            ->route('measurements.index', MeasurementFilters::fromRequest($request, $user)->toQuery())
            ->with('success', "{$deleted}件の測定を削除しました。");
    }

    private function findUpload(AppUser $user, int $id): Upload
    {
        $upload = Upload::withTrashed()
            ->with('farm')
            ->where('status', Upload::STATUS_COMPLETED)
            ->findOrFail($id);

        if ($upload->trashed() && !$user->isAdmin()) {
            abort(404);
        }

        Gate::forUser($user)->authorize('view', $upload);

        return $upload;
    }

    private function ensureNotTrashed(Upload $upload): void
    {
        if ($upload->trashed()) {
            abort(response()->json(['message' => '削除済みの測定は変更できません。'], 409));
        }
    }

    /**
     * 旧アップロード管理で扱っていた、推定結果の入力待ちの測定（管理者のみ）。
     *
     * @return Collection<int, Upload>
     */
    private function pendingUploads(): Collection
    {
        return Upload::query()
            ->with(['farm', 'analysisResult'])
            ->whereIn('status', [Upload::STATUS_UPLOADED, Upload::STATUS_PROCESSING])
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }
}
