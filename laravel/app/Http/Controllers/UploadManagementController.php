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

        // モーダル用データを事前に整形（ページネーションされたアイテムのみ）
        $uploadsForModal = $uploads->getCollection()->map(function($upload) {
            return [
                'id' => $upload->id,
                'farm_id' => $upload->farm_id,
                'file_path' => $upload->file_path,
                'measurement_date' => $upload->measurement_date ? $upload->measurement_date->format('Y-m-d') : null,
                'status' => $upload->status ?? 'uploaded',
                'note1' => $upload->note1,
                'note2' => $upload->note2,
                'cultivation_type' => $upload->cultivation_type,
                'measurement_parameters' => $upload->measurement_parameters,
                'owner_name' => $upload->farm->appUser->name ?? '-',
                'farm_name' => $upload->farm->farm_name ?? '-',
                'created_at' => $upload->created_at ? $upload->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $upload->updated_at ? $upload->updated_at->format('Y-m-d H:i:s') : null,
            ];
        })->values()->all();

        return view('upload_management.index', [
            'uploads' => $uploads,
            'uploadsForModal' => $uploadsForModal,
        ]);
    }

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
            
            return redirect()->route('upload-management.index')
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
     */
    public function download($id)
    {
        $upload = Upload::findOrFail($id);
        
        // S3の設定を取得（IAMロールが自動的に使用される）
        $disk = Storage::disk('s3');
        
        // ファイルが存在するか確認
        if (!$disk->exists($upload->file_path)) {
            abort(404, 'ファイルが見つかりませんでした。');
        }
        
        // ファイル名を取得（パスから最後の部分を取得）
        $fileName = basename($upload->file_path);
        
        // S3からファイルを取得してダウンロードレスポンスを返す
        // response()->streamDownload()を使用してメモリ効率を向上
        return response()->streamDownload(function () use ($disk, $upload) {
            echo $disk->get($upload->file_path);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
