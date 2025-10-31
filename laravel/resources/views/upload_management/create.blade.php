@extends('layouts.dashboard')

@section('title', '新規アップロード登録')
@section('header-title', '新規アップロード登録')

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow p-8">
            <h2 class="text-xl font-bold mb-6">新規アップロード登録</h2>
            
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('upload-management.store') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="farm_id" class="block font-semibold mb-2">圃場 <span class="text-red-500">*</span></label>
                    <select 
                        id="farm_id" 
                        name="farm_id" 
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('farm_id') border-red-500 @enderror"
                        required
                    >
                        <option value="">-- 圃場を選択してください --</option>
                        @foreach($farms as $farm)
                            <option value="{{ $farm->id }}" {{ old('farm_id') == $farm->id ? 'selected' : '' }}>
                                ID: {{ $farm->id }} - {{ $farm->farm_name }} (所有者: {{ $farm->appUser->name ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('farm_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="file_path" class="block font-semibold mb-2">ファイルパス（S3キー） <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        id="file_path" 
                        name="file_path" 
                        value="{{ old('file_path') }}"
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('file_path') border-red-500 @enderror" 
                        placeholder="measurements/{cognito_sub}/{iso8601}_{note1}_{note2}.csv"
                        required
                    >
                    @error('file_path')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">例: measurements/abc123/2025-10-30_note1_note2.csv</p>
                </div>

                <div>
                    <label for="measurement_date" class="block font-semibold mb-2">分析日付</label>
                    <input 
                        type="date" 
                        id="measurement_date" 
                        name="measurement_date" 
                        value="{{ old('measurement_date') }}"
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('measurement_date') border-red-500 @enderror"
                    >
                    @error('measurement_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block font-semibold mb-2">ステータス <span class="text-red-500">*</span></label>
                    <select 
                        id="status" 
                        name="status" 
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('status') border-red-500 @enderror"
                        required
                    >
                        <option value="uploaded" {{ old('status') == 'uploaded' ? 'selected' : '' }}>uploaded（アップロード済み）</option>
                        <option value="processing" {{ old('status') == 'processing' ? 'selected' : '' }}>processing（処理中）</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>completed（完了）</option>
                        <option value="exec_error" {{ old('status') == 'exec_error' ? 'selected' : '' }}>exec_error（エラー）</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="note1" class="block font-semibold mb-2">Note 1</label>
                        <input 
                            type="text" 
                            id="note1" 
                            name="note1" 
                            value="{{ old('note1') }}"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('note1') border-red-500 @enderror" 
                            placeholder="Note 1（任意）"
                        >
                        @error('note1')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="note2" class="block font-semibold mb-2">Note 2</label>
                        <input 
                            type="text" 
                            id="note2" 
                            name="note2" 
                            value="{{ old('note2') }}"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('note2') border-red-500 @enderror" 
                            placeholder="Note 2（任意）"
                        >
                        @error('note2')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="cultivation_type" class="block font-semibold mb-2">栽培種別</label>
                    <input 
                        type="text" 
                        id="cultivation_type" 
                        name="cultivation_type" 
                        value="{{ old('cultivation_type') }}"
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('cultivation_type') border-red-500 @enderror" 
                        placeholder="栽培種別（任意）"
                    >
                    @error('cultivation_type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="measurement_parameters" class="block font-semibold mb-2">測定パラメータ（JSON）</label>
                    <textarea 
                        id="measurement_parameters" 
                        name="measurement_parameters" 
                        rows="8"
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400 @error('measurement_parameters') border-red-500 @enderror" 
                        placeholder='{"parameter1": "value1", "parameter2": "value2"}'
                    >{{ old('measurement_parameters') }}</textarea>
                    @error('measurement_parameters')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">JSON形式で入力してください（任意）。例: {"CEC": 25.5, "pH": 6.8}</p>
                </div>

                <div class="flex flex-row gap-4 pt-4">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        登録
                    </button>
                    <a href="{{ route('upload-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-3 px-8 rounded-lg transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        キャンセル
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

