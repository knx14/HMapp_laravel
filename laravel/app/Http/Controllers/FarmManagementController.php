<?php

namespace App\Http\Controllers;

use App\Enums\WorkType;
use App\Http\Requests\SaveFarmRequest;
use App\Models\AppUser;
use App\Models\Farm;
use App\Services\Farms\FarmDeleter;
use App\Services\Results\FarmTimelineService;
use App\Services\Results\ResultsAggregationService;
use App\Services\Results\ResultsTimeseriesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class FarmManagementController extends Controller
{
    /** 並べ替えの選択肢。user_name は管理者のみ */
    public const SORTS = [
        'created_desc' => '登録日が新しい順',
        'created_asc' => '登録日が古い順',
        'farm_name' => '圃場名順',
        'user_name' => 'ユーザー名順',
    ];

    public function __construct(
        private FarmDeleter $deleter,
        private ResultsAggregationService $results,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $input = array_map(
            fn ($value) => is_string($value) ? trim($value) : null,
            $request->only(['farm_name', 'cultivation_method', 'crop_type', 'user_name', 'sort']),
        );
        $sort = array_key_exists($input['sort'] ?? '', self::SORTS) ? $input['sort'] : 'created_desc';
        if ($sort === 'user_name' && !$isAdmin) {
            $sort = 'created_desc';
        }

        $query = Farm::query()
            ->with('appUser')
            ->accessibleBy($user)
            ->leftJoin('app_users', 'app_users.id', '=', 'farms.app_user_id')
            ->select('farms.*');

        foreach (['farm_name' => 'farms.farm_name', 'cultivation_method' => 'farms.cultivation_method', 'crop_type' => 'farms.crop_type'] as $key => $column) {
            $this->whereLike($query, $column, $input[$key] ?? null);
        }
        if ($isAdmin) {
            $this->whereLike($query, 'app_users.name', $input['user_name'] ?? null);
        }

        match ($sort) {
            'created_asc' => $query->orderBy('farms.created_at'),
            'farm_name' => $query->orderBy('farms.farm_name'),
            'user_name' => $query->orderBy('app_users.name')->orderBy('farms.farm_name'),
            default => $query->orderByDesc('farms.created_at'),
        };
        $query->orderBy('farms.id');

        $farms = $query->paginate(12)->appends(array_filter($input + ['sort' => $sort]));

        return view('farm_management.index', [
            'farms' => $farms,
            'input' => $input,
            'sort' => $sort,
            'sorts' => $isAdmin ? self::SORTS : array_diff_key(self::SORTS, ['user_name' => true]),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show(
        Request $request,
        Farm $farm,
        ResultsTimeseriesService $timeseries,
        FarmTimelineService $timeline,
    ) {
        Gate::authorize('view', $farm);

        $dates = $this->results->getCompletedDistinctDatesForFarm((int) $farm->id);
        $selectedDate = (string) $request->query('date', '');
        if (! in_array($selectedDate, $dates, true)) {
            $selectedDate = $dates[0] ?? null;
        }

        $series = [];
        foreach ($timeseries->allowedParameters() as $parameter) {
            $series[$parameter] = $timeseries->get((int) $farm->id, $parameter);
        }

        return view('farm_management.show', [
            'farm' => $farm->load('appUser'),
            'dates' => $dates,
            'selectedDate' => $selectedDate,
            'points' => $selectedDate ? $this->results->fetchPointsForFarmDate((int) $farm->id, $selectedDate) : [],
            'boundary' => $this->results->normalizeBoundaryPolygon($farm->boundary_polygon),
            'series' => $series,
            'timeline' => $timeline->get((int) $farm->id),
            'canManageWorkLogs' => Gate::allows('manage', $farm),
            'workTypes' => collect(WorkType::cases())->mapWithKeys(fn (WorkType $type) => [$type->value => $type->label()]),
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function create(Request $request)
    {
        return view('farm_management.form', [
            'farm' => null,
            'boundary' => [],
            'owners' => $this->owners($request->user()),
        ]);
    }

    public function store(SaveFarmRequest $request): RedirectResponse
    {
        $user = $request->user();

        $farm = Farm::create([
            'app_user_id' => $user->isAdmin() ? (int) $request->validated('app_user_id') : $user->id,
            'farm_name' => $request->validated('farm_name'),
            'cultivation_method' => $request->validated('cultivation_method'),
            'crop_type' => $request->validated('crop_type'),
            'boundary_polygon' => $request->boundary(),
        ]);

        return redirect()->route('farm-management.index')
            ->with('success', $farm->isProvisional()
                ? "「{$farm->farm_name}」を仮登録しました（境界なし）。"
                : "「{$farm->farm_name}」を登録しました。");
    }

    public function edit(Request $request, Farm $farm)
    {
        Gate::authorize('manage', $farm);

        return view('farm_management.form', [
            'farm' => $farm->load('appUser'),
            'boundary' => $this->results->normalizeBoundaryPolygon($farm->boundary_polygon),
            'owners' => $this->owners($request->user()),
        ]);
    }

    public function update(SaveFarmRequest $request, Farm $farm): RedirectResponse
    {
        Gate::authorize('manage', $farm);

        $farm->fill([
            'farm_name' => $request->validated('farm_name'),
            'cultivation_method' => $request->validated('cultivation_method'),
            'crop_type' => $request->validated('crop_type'),
            'boundary_polygon' => $request->boundary(),
        ]);

        $newOwnerId = $request->validated('app_user_id');
        if ($newOwnerId !== null && (int) $newOwnerId !== (int) $farm->app_user_id && $request->user()->can('changeOwner', $farm)) {
            Log::info('farm owner changed', [
                'farm_id' => $farm->id,
                'from_app_user_id' => $farm->app_user_id,
                'to_app_user_id' => (int) $newOwnerId,
                'by_app_user_id' => $request->user()->id,
            ]);
            $farm->app_user_id = (int) $newOwnerId;
        }

        $farm->save();

        return redirect()->route('farm-management.index')
            ->with('success', "「{$farm->farm_name}」を更新しました。");
    }

    public function destroy(Farm $farm): RedirectResponse
    {
        Gate::authorize('manage', $farm);

        $message = $this->deleter->delete($farm) === FarmDeleter::HIDDEN
            ? "「{$farm->farm_name}」を削除しました（測定・作業記録があるため、データは残して非表示にしました）。"
            : "「{$farm->farm_name}」を削除しました。";

        return redirect()->route('farm-management.index')->with('success', $message);
    }

    /**
     * 指定された圃場の境界線データを取得する
     */
    public function getBoundary(Request $request, int $farmId): JsonResponse
    {
        $farm = Farm::find($farmId);

        if (!$farm) {
            return response()->json([
                'error' => '指定された圃場が見つかりません。',
                'message' => 'Farm not found'
            ], 404);
        }

        if ($request->user()->cannot('view', $farm)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$farm->boundary_polygon) {
            return response()->json([
                'error' => 'この圃場には境界線データが設定されていません。',
                'message' => 'No boundary data available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'farm_id' => $farm->id,
                'farm_name' => $farm->farm_name,
                'boundary_polygon' => $farm->boundary_polygon
            ]
        ]);
    }

    /**
     * 管理者が所有者を選ぶための候補。一般ユーザーには出さない。
     *
     * @return Collection<int, AppUser>
     */
    private function owners(AppUser $user): Collection
    {
        if (!$user->isAdmin()) {
            return collect();
        }

        return AppUser::query()->orderBy('name')->get(['id', 'name', 'email', 'organization']);
    }

    private function whereLike($query, string $column, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query->where($column, 'like', '%'.addcslashes($value, '\\%_').'%');
    }
}
