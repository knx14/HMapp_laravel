<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\Farm;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UploadManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Upload::with(['farm.appUser']);

        // 圃場所有者の名前で検索
        if ($request->filled('owner_name')) {
            $query->whereHas('farm.appUser', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->owner_name . '%')
                  ->orWhere('ja_name', 'like', '%' . $request->owner_name . '%');
            });
        }

        // 分析日付で検索
        if ($request->filled('measurement_date')) {
            $query->where('measurement_date', $request->measurement_date);
        }

        $uploads = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('upload_management.index', compact('uploads'));
    }
}
