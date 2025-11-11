@extends('layouts.dashboard')

@section('title', '推定結果閲覧 - 結果入力')
@section('header-title', '推定結果閲覧 - 結果入力')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-2">圃場情報</h2>
            <p class="text-gray-700"><span class="font-semibold">ID:</span> {{ $farm->id }}</p>
            <p class="text-gray-700"><span class="font-semibold">農場名:</span> {{ $farm->farm_name }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">測定点の入力</h3>

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

            @if($pendingUploads->isEmpty())
                <div class="text-gray-500">入力待ちのアップロードがありません。</div>
            @else
                <form method="POST" action="{{ route('estimation-results.store-analysis-result', ['farm' => $farm->id]) }}">
                    @csrf

                    <div class="mb-4">
                        <label for="upload_id" class="block text-sm font-medium text-gray-700 mb-2">
                            アップロードID <span class="text-red-500">*</span>
                        </label>
                        <select name="upload_id" id="upload_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">選択してください</option>
                            @foreach($pendingUploads as $upload)
                                <option value="{{ $upload->id }}" {{ old('upload_id', $selectedUploadId) == $upload->id ? 'selected' : '' }}>
                                    ID: {{ $upload->id }} - {{ $upload->measurement_date ? $upload->measurement_date->format('Y-m-d') : '日付不明' }}
                                </option>
                            @endforeach
                        </select>
                        @error('upload_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="sensor_info" class="block text-sm font-medium text-gray-700 mb-2">
                            センサー情報 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="sensor_info" id="sensor_info" 
                               value="{{ old('sensor_info') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                               required>
                        @error('sensor_info')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">
                                緯度 (Latitude) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="any" name="latitude" id="latitude" 
                                   value="{{ old('latitude') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   required>
                            @error('latitude')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">
                                経度 (Longitude) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="any" name="longitude" id="longitude" 
                                   value="{{ old('longitude') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   required>
                            @error('longitude')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
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
            @endif
        </div>
    </div>
</div>
@endsection
