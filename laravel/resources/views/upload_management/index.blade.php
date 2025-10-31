@extends('layouts.dashboard')

@section('title', 'アップロード管理')
@section('header-title', 'アップロード管理')

@section('content')
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 mx-4">
        {{ session('success') }}
    </div>
@endif

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- 検索フォーム -->
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('upload-management.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">圃場所有者名</label>
                        <input type="text" 
                               name="owner_name" 
                               value="{{ request('owner_name') }}"
                               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                               placeholder="所有者名を入力">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">分析日付</label>
                        <input type="date" 
                               name="measurement_date" 
                               value="{{ request('measurement_date') }}"
                               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                <div class="flex flex-row gap-4 mt-6">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        検索
                    </button>
                    <a href="{{ route('upload-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        リセット
                    </a>
                </div>
            </form>
        </div>

        <!-- 結果表示 -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">アップロード一覧</h2>
                        <p class="text-gray-600 mt-1">全{{ $uploads->total() }}件中 {{ $uploads->firstItem() ?? 0 }}-{{ $uploads->lastItem() ?? 0 }}件を表示</p>
                    </div>
                    <a href="{{ route('upload-management.create') }}" 
                       class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        新規アップロード登録
                    </a>
                </div>
            </div>

            @if($uploads->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">ID</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">Farm ID</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">ファイルパス</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">分析日付</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">ステータス</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">圃場所有者</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($uploads as $upload)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="showUploadDetail({{ $upload->id }})">
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $upload->id }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $upload->farm_id }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">
                                        @php
                                            $pathParts = explode('/', $upload->file_path);
                                            $fileName = end($pathParts);
                                            $displayPath = strlen($fileName) > 30 ? substr($fileName, 0, 30) . '...' : $fileName;
                                        @endphp
                                        <span title="{{ $upload->file_path }}">{{ $displayPath }}</span>
                                    </td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $upload->measurement_date ? $upload->measurement_date->format('Y-m-d') : '-' }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">
                                        @php
                                            $statusClasses = [
                                                'uploaded' => 'bg-gray-200 text-gray-800',
                                                'processing' => 'bg-blue-200 text-blue-800',
                                                'completed' => 'bg-green-200 text-green-800',
                                                'exec_error' => 'bg-red-200 text-red-800',
                                            ];
                                            $statusTexts = [
                                                'uploaded' => 'アップロード済み',
                                                'processing' => '処理中',
                                                'completed' => '完了',
                                                'exec_error' => 'エラー',
                                            ];
                                            $status = $upload->status ?? 'uploaded';
                                            $statusClass = $statusClasses[$status] ?? 'bg-gray-200 text-gray-800';
                                            $statusText = $statusTexts[$status] ?? $status;
                                        @endphp
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }} cursor-help" 
                                              title="{{ $statusText }} ({{ $status }})&#10;&#10;ステータス説明:&#10;- uploaded: アップロード済み（グレー）&#10;- processing: 処理中（ブルー）&#10;- completed: 完了（グリーン）&#10;- exec_error: エラー（レッド）">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $upload->farm->appUser->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- ページネーション -->
                <div class="px-8 py-6 border-t border-gray-200">
                    {{ $uploads->appends(request()->query())->links() }}
                </div>
            @else
                <div class="px-8 py-12 text-center">
                    <div class="text-gray-500 text-lg">検索条件に一致するアップロードが見つかりませんでした。</div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- モーダル -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">アップロード詳細</h3>
                <button onclick="closeUploadDetail()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="modalContent" class="space-y-4">
                <!-- コンテンツはJavaScriptで動的に生成 -->
            </div>
        </div>
    </div>
</div>

<script>
// アップロードデータ（サーバー側から事前に整形済み）
const uploadsData = @json($uploadsForModal);

function showUploadDetail(uploadId) {
    const upload = uploadsData.find(u => u.id === uploadId);
    if (!upload) return;
    
    const modal = document.getElementById('uploadModal');
    const content = document.getElementById('modalContent');
    
    // ステータスの色分け
    const statusClasses = {
        'uploaded': 'bg-gray-200 text-gray-800',
        'processing': 'bg-blue-200 text-blue-800',
        'completed': 'bg-green-200 text-green-800',
        'exec_error': 'bg-red-200 text-red-800',
    };
    const statusTexts = {
        'uploaded': 'アップロード済み',
        'processing': '処理中',
        'completed': '完了',
        'exec_error': 'エラー',
    };
    const status = upload.status || 'uploaded';
    const statusClass = statusClasses[status] || 'bg-gray-200 text-gray-800';
    const statusText = statusTexts[status] || status;
    
    // JSONメタデータの整形表示
    let parametersHtml = '<p class="text-gray-500">なし</p>';
    if (upload.measurement_parameters && Object.keys(upload.measurement_parameters).length > 0) {
        parametersHtml = '<pre class="bg-gray-100 p-4 rounded overflow-x-auto text-sm">' + 
                         JSON.stringify(upload.measurement_parameters, null, 2) + 
                         '</pre>';
    }
    
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">ID</label>
                <p class="text-gray-900">${upload.id}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Farm ID</label>
                <p class="text-gray-900">${upload.farm_id}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">圃場所有者</label>
                <p class="text-gray-900">${upload.owner_name}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">圃場名</label>
                <p class="text-gray-900">${upload.farm_name}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">ファイルパス</label>
                <p class="text-gray-900 break-all">${upload.file_path}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">分析日付</label>
                <p class="text-gray-900">${upload.measurement_date || '-'}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">ステータス</label>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">
                    ${status} (${statusText})
                </span>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">栽培種別</label>
                <p class="text-gray-900">${upload.cultivation_type || '-'}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Note 1</label>
                <p class="text-gray-900">${upload.note1 || '-'}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Note 2</label>
                <p class="text-gray-900">${upload.note2 || '-'}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">作成日時</label>
                <p class="text-gray-900">${upload.created_at || '-'}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">更新日時</label>
                <p class="text-gray-900">${upload.updated_at || '-'}</p>
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">測定パラメータ（JSON）</label>
            ${parametersHtml}
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function closeUploadDetail() {
    document.getElementById('uploadModal').classList.add('hidden');
}

// モーダル外クリックで閉じる
document.getElementById('uploadModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUploadDetail();
    }
});

// ESCキーで閉じる
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUploadDetail();
    }
});
</script>
@endsection
