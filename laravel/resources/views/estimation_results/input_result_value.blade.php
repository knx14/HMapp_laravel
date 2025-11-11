@extends('layouts.dashboard')

@section('title', '推定結果閲覧 - 測定値入力')
@section('header-title', '推定結果閲覧 - 測定値入力')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-2">圃場情報</h2>
            <p class="text-gray-700"><span class="font-semibold">ID:</span> {{ $farm->id }}</p>
            <p class="text-gray-700"><span class="font-semibold">農場名:</span> {{ $farm->farm_name }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">測定点情報</h3>
            <p class="text-gray-700"><span class="font-semibold">緯度:</span> {{ $analysisResult->latitude }}</p>
            <p class="text-gray-700"><span class="font-semibold">経度:</span> {{ $analysisResult->longitude }}</p>
            <p class="text-gray-700"><span class="font-semibold">センサー情報:</span> {{ $analysisResult->sensor_info }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">測定値の入力</h3>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('estimation-results.store-result-value', ['farm' => $farm->id, 'analysisResult' => $analysisResult->id]) }}" id="resultValueForm">
                @csrf

                <div id="parameters-container">
                    <div class="parameter-row mb-4 p-4 border border-gray-300 rounded-md">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    パラメータ名 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="parameters[0][name]" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    値 <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="any" name="parameters[0][value]" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    単位
                                </label>
                                <input type="text" name="parameters[0][unit]" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <button type="button" class="mt-2 px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 remove-parameter" disabled>
                            削除（1つ以上入力してください）
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <button type="button" id="add-parameter" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        パラメータを追加
                    </button>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('estimation-results.farm-dates', ['farm' => $farm->id]) }}" 
                       class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        キャンセル
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        送信
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let parameterIndex = 1;

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.parameter-row');
    const removeButtons = document.querySelectorAll('.remove-parameter');
    
    // パラメータが1つしかない場合は削除ボタンを無効化
    if (rows.length <= 1) {
        removeButtons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.classList.remove('hover:bg-red-600');
        });
    } else {
        removeButtons.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.classList.add('hover:bg-red-600');
        });
    }
}

document.getElementById('add-parameter').addEventListener('click', function() {
    const container = document.getElementById('parameters-container');
    const newRow = document.createElement('div');
    newRow.className = 'parameter-row mb-4 p-4 border border-gray-300 rounded-md';
    newRow.innerHTML = `
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    パラメータ名 <span class="text-red-500">*</span>
                </label>
                <input type="text" name="parameters[${parameterIndex}][name]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    値 <span class="text-red-500">*</span>
                </label>
                <input type="number" step="any" name="parameters[${parameterIndex}][value]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    単位
                </label>
                <input type="text" name="parameters[${parameterIndex}][unit]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <button type="button" class="mt-2 px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 remove-parameter">削除</button>
    `;
    container.appendChild(newRow);
    parameterIndex++;

    // 削除ボタンのイベントリスナー
    newRow.querySelector('.remove-parameter').addEventListener('click', function() {
        newRow.remove();
        updateRemoveButtons();
    });
    
    updateRemoveButtons();
});

// 既存の削除ボタンにもイベントリスナーを追加
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-parameter') && !e.target.disabled) {
        e.target.closest('.parameter-row').remove();
        updateRemoveButtons();
    }
});

// 初期状態で削除ボタンの状態を更新
updateRemoveButtons();
</script>
@endsection
