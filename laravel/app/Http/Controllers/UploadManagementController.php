<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\Farm;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    /**
     * S3からCSVファイルをダウンロード
     * EC2のIAMロールを使用して認証
     * file_pathをクエリパラメータで受け取り、DB接続不要でS3から直接ダウンロード
     */
    public function download(Request $request)
    {
        $filePath = $request->query('path');
        
        if (!$filePath) {
            abort(400, 'ファイルパスが指定されていません。');
        }
        
        // セキュリティ: パストラバーサル攻撃を防ぐため、相対パスや危険な文字をチェック
        if (strpos($filePath, '..') !== false || strpos($filePath, "\0") !== false) {
            abort(400, '無効なファイルパスです。');
        }
        
        // S3の設定を取得（IAMロールが自動的に使用される）
        $disk = Storage::disk('s3');
        
        // ファイルが存在するか確認
        if (!$disk->exists($filePath)) {
            abort(404, 'ファイルが見つかりませんでした。');
        }
        
        // ファイル名を取得（パスから最後の部分を取得）
        $fileName = basename($filePath);
        
        // S3からストリームで読み出してそのまま返す（大きいCSVでもメモリに乗せない）
        $stream = $disk->readStream($filePath);
        if ($stream === false) {
            abort(500, 'ファイルストリームの取得に失敗しました。');
        }

        return response()->streamDownload(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
