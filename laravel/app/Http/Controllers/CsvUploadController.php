<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MainData;

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
            $handle = fopen($file, 'r');
            fgetcsv($handle); // ヘッダー行をスキップ

        $userId = Auth::id();
        $userTable = "user_{$userId}_data";

        // ユーザー専用テーブルが存在しなければ作成
        if (!Schema::hasTable($userTable)) {
            Schema::create($userTable, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->dateTime('date_time');
                $table->integer('total_count');
                $table->string('memo')->nullable();
                $table->integer('count1');
                $table->integer('count2');
                $table->string('command')->nullable();
                $table->text('frequency')->nullable();
                $table->text('c_real')->nullable();
                $table->text('c_image')->nullable();
                $table->timestamps();
            });
        }
        //データをグループ化
        $grouped = [];            

            while (($row = fgetcsv($handle)) !== false) {
                $key = implode('_', [$row[0], intval($row[1]), intval($row[3]),intval($row[4])]);
                
                if(!isset($grouped[$key])){
                    $grouped[$key] = [
                    'user_id'     => $userId,
                    'date_time'   => $row[0],
                    'total_count' => intval($row[1]),
                    'memo'        => $row[2],
                    'count1'      => intval($row[3]),
                    'count2'      => intval($row[4]),
                    'command'     => $row[6],
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
            //保存処理(main_data+個別テーブルへの格納)
            foreach($grouped as $data){
                // main_data にも保存
                MainData::create($data);
                // ユーザーごとのテーブルにも保存
                DB::table($userTable)->insert($data);
            }
            return redirect()->back()->with('success', 'CSVを保存しました');
        }
    }




