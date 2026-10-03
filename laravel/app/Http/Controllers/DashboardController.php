<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Upload;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** ダッシュボードに出す最近の推定結果の件数 */
    private const RECENT_RESULTS_LIMIT = 10;

    public function index(): View
    {
        $userCount = AppUser::count();
        $uploadCount = Upload::count();
        $completedCount = Upload::where('status', Upload::STATUS_COMPLETED)->count();

        $recentResults = Upload::query()
            ->join('farms', 'farms.id', '=', 'uploads.farm_id')
            ->leftJoin('app_users', 'app_users.id', '=', 'farms.app_user_id')
            ->select([
                'uploads.id',
                'uploads.measurement_date',
                'uploads.measured_at',
                'uploads.measurement_number',
                'farms.farm_name',
                'farms.cultivation_method',
                'farms.crop_type',
                'app_users.name as user_name',
            ])
            ->where('uploads.status', Upload::STATUS_COMPLETED)
            ->orderByDesc('uploads.measurement_date')
            ->orderByDesc('uploads.measured_at')
            ->orderByDesc('uploads.id')
            ->limit(self::RECENT_RESULTS_LIMIT)
            ->get();

        return view('dashboard', [
            'userCount' => $userCount,
            'uploadCount' => $uploadCount,
            'completedCount' => $completedCount,
            'recentResults' => $recentResults,
        ]);
    }
} 