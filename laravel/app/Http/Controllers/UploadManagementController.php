<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\Upload;
use Illuminate\Http\Request;

class UploadManagementController extends Controller
{
    /**
     * 新規アップロード登録フォームを表示
     */
    public function create()
    {
        $farms = Farm::with('appUser')->orderBy('id')->get();
        return view('upload_management.create', compact('farms'));
    }

    /**
     * 新規アップロードを登録
     */
    public function store(Request $request)
    {
        $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'file_path' => 'required|string|max:255|unique:uploads,file_path',
            'measurement_date' => 'nullable|date',
            'status' => 'required|in:uploaded,processing,completed,exec_error',
            'note1' => 'nullable|string|max:255',
            'note2' => 'nullable|string|max:255',
            'cultivation_type' => 'nullable|string|max:255',
            'measurement_parameters' => 'nullable|json',
        ], [
            'farm_id.required' => '圃場を選択してください。',
            'farm_id.exists' => '選択された圃場が存在しません。',
            'file_path.required' => 'ファイルパスは必須です。',
            'file_path.unique' => 'このファイルパスは既に登録されています。',
            'status.required' => 'ステータスを選択してください。',
            'status.in' => '無効なステータスです。',
            'measurement_parameters.json' => '測定パラメータは有効なJSON形式で入力してください。',
        ]);

        $data = $request->only([
            'farm_id',
            'file_path',
            'measurement_date',
            'status',
            'note1',
            'note2',
            'cultivation_type',
        ]);

        // measurement_parametersをJSON形式で処理
        if ($request->filled('measurement_parameters')) {
            $jsonData = json_decode($request->measurement_parameters, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['measurement_parameters'] = $jsonData;
            } else {
                return redirect()->back()
                    ->withErrors(['measurement_parameters' => '測定パラメータは有効なJSON形式で入力してください。'])
                    ->withInput();
            }
        }

        try {
            Upload::create($data);
            
            return redirect()->route('measurements.index')
                ->with('success', 'アップロードが正常に登録されました。');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'アップロードの登録中にエラーが発生しました。'])
                ->withInput();
        }
    }
}
