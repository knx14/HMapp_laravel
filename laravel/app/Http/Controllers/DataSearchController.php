<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Measurement;

class DataSearchController extends Controller
{
    // 検索フォーム表示
    public function index(Request $request)
    {
        $input = $request->only(['cognito_id', 'location_info', 'serial_no']);

        // すべて空なら結果はnull
        if (empty($input['cognito_id']) && empty($input['location_info']) && empty($input['serial_no'])) {
            return view('data_search.index', [
                'results' => null,
                'input' => [
                    'cognito_id' => '',
                    'location_info' => '',
                    'serial_no' => '',
                ],
            ]);
        }

        $query = Measurement::query();
        if ($input['cognito_id']) {
            $query->where('cognito_id', 'like', '%' . $input['cognito_id'] . '%');
        }
        if ($input['location_info']) {
            $query->where('location_info', 'like', '%' . $input['location_info'] . '%');
        }
        if ($input['serial_no']) {
            $query->where('serial_no', 'like', '%' . $input['serial_no'] . '%');
        }
        $results = $query->orderByDesc('id')->get();

        return view('data_search.index', [
            'results' => $results,
            'input' => $input,
        ]);
    }
}
//     // 検索結果をCSVで出力
//     public function export(Request $request)
//     {
//         $input = $request->only(['cognito_id', 'location_info', 'serial_no']);

//         // すべて空なら空のCSVを返す
//         if (empty($input['cognito_id']) && empty($input['location_info']) && empty($input['serial_no'])) {
//             $results = collect();
//         } else {
//             $query = Measurement::query();
//             if ($input['cognito_id']) {
//                 $query->where('cognito_id', 'like', '%' . $input['cognito_id'] . '%');
//             }
//             if ($input['location_info']) {
//                 $query->where('location_info', 'like', '%' . $input['location_info'] . '%');
//             }
//             if ($input['serial_no']) {
//                 $query->where('serial_no', 'like', '%' . $input['serial_no'] . '%');
//             }
//             $results = $query->orderByDesc('id')->get();
//         }

//         $headers = [
//             'Content-Type' => 'text/csv; charset=UTF-8',
//             'Content-Disposition' => 'attachment; filename="search_results.csv"',
//         ];

//         $columns = ['id', 'cognito_id', 'location_info', 'serial_no', 'created_at', 'updated_at'];

//         $callback = function() use ($results, $columns) {
//             $file = fopen('php://output', 'w');
//             // UTF-8 BOM
//             fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));
//             // ヘッダー
//             fputcsv($file, $columns);
//             // データ
//             foreach ($results as $row) {
//                 $data = [];
//                 foreach ($columns as $col) {
//                     $data[] = $row[$col];
//                 }
//                 fputcsv($file, $data);
//             }
//             fclose($file);
//         };

//         return response()->stream($callback, 200, $headers);
//     }
// } 