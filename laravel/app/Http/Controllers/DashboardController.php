<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Measurement;

class DashboardController extends Controller
{
    public function index()
    {
        // cognito_idのユニーク数
        $userCount = Measurement::distinct('cognito_id')->count('cognito_id');
        // 全行数
        $uploadCount = Measurement::count();

        return view('dashboard', [
            'userCount' => $userCount,
            'uploadCount' => $uploadCount,
        ]);
    }
} 