<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Measurement;
use App\Models\AppUser;
use App\Models\Upload;

class DashboardController extends Controller
{
    public function index()
    {
        // app_usersのユーザー数
        $userCount = AppUser::count();
        // アップロード数
        $uploadCount = Upload::count();
        // 演算処理数（statusが'completed'のuploadsの数）
        $completedCount = Upload::where('status', 'completed')->count();

        return view('dashboard', [
            'userCount' => $userCount,
            'uploadCount' => $uploadCount,
            'completedCount' => $completedCount,
        ]);
    }
} 