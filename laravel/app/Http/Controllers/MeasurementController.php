<?php

namespace App\Http\Controllers;

use App\Services\Measurements\MeasurementCsvExporter;
use App\Services\Measurements\MeasurementFilters;
use App\Services\Measurements\MeasurementQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeasurementController extends Controller
{
    public function __construct(
        private MeasurementQuery $measurements,
        private MeasurementCsvExporter $exporter,
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
}
