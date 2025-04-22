<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CsvFile;
use App\Models\CsvDataRow;

class CsvUploadController extends Controller
{
    public function create()
    {
        return view('csv.upload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $userId = Auth::id();

        //ファイル名を取得
        $filename = $file->getClientOriginalName();

        //csv_filesテーブルに保存
        $csvFile = CsvFile::create([
            'user_id' => $userId,
            'filename'=> $filename,
        ]);

        //csvファイルデータの読み取り
        $handle = fopen($file, 'r');
        fgetcsv($handle); // ヘッダー行をスキップ
        
        //データをグループ化
        $grouped = [];            

            while (($row = fgetcsv($handle)) !== false) {
                $key = implode('_', [$row[0], intval($row[1]), intval($row[3]),intval($row[4])]);
                
                if(!isset($grouped[$key])){
                    $grouped[$key] = [
                    'csv_file_id' => $csvFile->id,
                    'user_id'     => $userId,
                    'date_time'   => $row[0],
                    'total_count' => intval($row[1]),
                    'memo'        => $row[2],
                    'count1'      => intval($row[3]),
                    'count2'      => intval($row[4]),
                    'command'     => $row[6] ?? null,
                    'frequency'   => null,
                    'c_real'      => null,
                    'c_imag'      => null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                    ];   
                }
                
                $dataType = trim($row[7]);
                $values = array_slice($row, 8);
                $serialized = serialize($values);

                // データタイプ別に格納
                if ($dataType === 'Frequency') {
                    $grouped[$key]['frequency'] = $serialized;
                } elseif ($dataType === 'C-Real') {
                    $grouped[$key]['c_real'] = $serialized;
                } elseif ($dataType === 'C-Imag') {
                    $grouped[$key]['c_imag'] = $serialized;
                }
            }

            fclose($handle);
            //保存処理
            foreach($grouped as $data){
                // CsvDataRow に保存
                CsvDataRow::create($data);
            }
            return redirect()->back()->with('success', 'CSVを保存しました');
        }
    }