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
                $table->text('c_imag')->nullable();
                $table->timestamps();
            });
        }

        try {
            $file = $request->file('csv_file');
            $handle = fopen($file, 'r');
            fgetcsv($handle); // ヘッダー行をスキップ

            while (($row = fgetcsv($handle)) !== false) {
                $dataType = trim($row[7]);
                $values = array_slice($row, 8);
                $serialized = serialize($values);

                $baseData = [
                    'user_id'     => $userId,
                    'date_time'   => now(),
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

                // データタイプ別に格納
                if ($dataType === 'Frequency') {
                    $baseData['frequency'] = $serialized;
                    // main_data にも保存
                    MainData::create($baseData);
                    // ユーザーごとのテーブルにも保存
                    DB::table($userTable)->insert($baseData);
                } elseif ($dataType === 'C-Real') {
                    $baseData['c_real'] = $serialized;
                    MainData::create($baseData);
                    DB::table($userTable)->insert($baseData);
                } elseif ($dataType === 'C-Imag') {
                    $baseData['c_imag'] = $serialized;
                    MainData::create($baseData);
                    DB::table($userTable)->insert($baseData);
                }

                
            }

            fclose($handle);
            return redirect()->back()->with('success', 'CSVを保存しました');
        } catch (\Exception $e) {
            Log::error('CSVアップロード処理でエラーが発生: ' . $e->getMessage());
            return redirect()->back()->with('error', 'データの保存中に問題が発生しました。');
        }
    }
}



