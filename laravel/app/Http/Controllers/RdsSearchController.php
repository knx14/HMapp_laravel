<?php

namespace App\Http\Controllers;

use App\Models\RdsData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RdsSearchController extends Controller
{
    /**
     * 検索フォームを表示
     */
    public function index()
    {
        return view('rds.search');
    }

    /**
     * 検索を実行
     */
    public function search(Request $request)
    {
        try {
            $query = RdsData::query();

            // ユーザーID（cognito_id）で検索
            if ($request->filled('cognito_id')) {
                $query->byCognitoId($request->cognito_id);
            }

            // 開始日で検索
            if ($request->filled('start_date')) {
                $startDate = $request->start_date . ' 00:00:00';
                $query->where('created_at', '>=', $startDate);
            }

            // 終了日で検索
            if ($request->filled('end_date')) {
                $endDate = $request->end_date . ' 23:59:59';
                $query->where('created_at', '<=', $endDate);
            }

            // 特定の日付で検索
            if ($request->filled('date')) {
                $query->byDate($request->date);
            }

            // 結果を取得（ページネーション付き）
            $results = $query->orderBy('created_at', 'desc')
                           ->paginate(20);

            return view('rds.search', [
                'results' => $results,
                'searchParams' => $request->only(['cognito_id', 'start_date', 'end_date', 'date'])
            ]);

        } catch (\Exception $e) {
            Log::error('RDS検索エラー: ' . $e->getMessage());
            
            return view('rds.search')->withErrors([
                'error' => '検索中にエラーが発生しました: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 検索結果をJSONで返す（API用）
     */
    public function searchApi(Request $request)
    {
        try {
            $query = RdsData::query();

            // ユーザーID（cognito_id）で検索
            if ($request->filled('cognito_id')) {
                $query->byCognitoId($request->cognito_id);
            }

            // 開始日で検索
            if ($request->filled('start_date')) {
                $startDate = $request->start_date . ' 00:00:00';
                $query->where('created_at', '>=', $startDate);
            }

            // 終了日で検索
            if ($request->filled('end_date')) {
                $endDate = $request->end_date . ' 23:59:59';
                $query->where('created_at', '<=', $endDate);
            }

            // 特定の日付で検索
            if ($request->filled('date')) {
                $query->byDate($request->date);
            }

            // 結果を取得
            $results = $query->orderBy('created_at', 'desc')
                           ->get();

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => $results->count()
            ]);

        } catch (\Exception $e) {
            Log::error('RDS検索APIエラー: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => '検索中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }
} 