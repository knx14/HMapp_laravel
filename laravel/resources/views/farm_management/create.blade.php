@extends('layouts.dashboard')

@section('title', '圃場登録')
@section('header-title', '圃場登録')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- 戻るボタン -->
        <div class="mb-6">
            <a href="{{ route('farm-management.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                圃場管理に戻る
            </a>
        </div>

        <!-- 登録フォーム -->
        <div class="bg-white rounded-2xl shadow p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">新しい圃場を登録</h2>
            
            <form method="POST" action="{{ route('farm-management.store') }}" class="space-y-6">
                @csrf
                
                <!-- 保有者名 -->
                <div>
                    <label for="owner_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        保有者名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="owner_name" 
                           name="owner_name" 
                           value="{{ old('owner_name') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('owner_name') border-red-500 @enderror"
                           placeholder="保有者の名前を入力してください"
                           required>
                    @error('owner_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">登録済みのユーザー名を入力してください</p>
                </div>

                <!-- 圃場名 -->
                <div>
                    <label for="farm_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        圃場名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="farm_name" 
                           name="farm_name" 
                           value="{{ old('farm_name') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('farm_name') border-red-500 @enderror"
                           placeholder="圃場の名前を入力してください"
                           required>
                    @error('farm_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 栽培方法 -->
                <div>
                    <label for="cultivation_method" class="block text-sm font-semibold text-gray-700 mb-2">
                        栽培方法
                    </label>
                    <input type="text" 
                           id="cultivation_method" 
                           name="cultivation_method" 
                           value="{{ old('cultivation_method') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('cultivation_method') border-red-500 @enderror"
                           placeholder="例：水田、畑、ハウス栽培など">
                    @error('cultivation_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 作物種別 -->
                <div>
                    <label for="crop_type" class="block text-sm font-semibold text-gray-700 mb-2">
                        作物種別
                    </label>
                    <input type="text" 
                           id="crop_type" 
                           name="crop_type" 
                           value="{{ old('crop_type') }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('crop_type') border-red-500 @enderror"
                           placeholder="例：米、トマト、キャベツなど">
                    @error('crop_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- GPS情報 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        境界線GPS情報 <span class="text-red-500">*</span>
                    </label>
                    <p class="text-sm text-gray-600 mb-4">圃場の境界線を表すGPS座標を入力してください。最低4点、最大8点まで入力可能です。</p>
                    
                    <div class="space-y-3" id="gps-inputs">
                        @for($i = 1; $i <= 8; $i++)
                        <div class="flex items-center space-x-4 p-3 border border-gray-200 rounded-lg {{ $i <= 4 ? 'bg-blue-50' : '' }}">
                            <div class="flex-shrink-0 w-8 text-center font-medium text-gray-600">
                                点{{ $i }}
                                @if($i <= 4)
                                    <span class="text-red-500">*</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">緯度 (Latitude)</label>
                                <input type="number" 
                                       step="any" 
                                       name="gps_lat_{{ $i }}" 
                                       value="{{ old('gps_lat_' . $i) }}"
                                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('gps_lat_' . $i) border-red-500 @enderror"
                                       placeholder="例: 35.6762"
                                       {{ $i <= 4 ? 'required' : '' }}>
                                @error('gps_lat_' . $i)
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">経度 (Longitude)</label>
                                <input type="number" 
                                       step="any" 
                                       name="gps_lng_{{ $i }}" 
                                       value="{{ old('gps_lng_' . $i) }}"
                                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('gps_lng_' . $i) border-red-500 @enderror"
                                       placeholder="例: 139.6503"
                                       {{ $i <= 4 ? 'required' : '' }}>
                                @error('gps_lng_' . $i)
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        @endfor
                    </div>
                    
                    @error('gps_coordinates')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 送信ボタン -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('farm-management.index') }}" 
                       class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        キャンセル
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                        圃場を登録
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 必須項目（最初の4点）の入力チェック
    const requiredInputs = document.querySelectorAll('input[name^="gps_lat_"], input[name^="gps_lng_"]');
    
    requiredInputs.forEach((input, index) => {
        if (index < 8) { // 最初の4点（8つの入力フィールド）
            input.addEventListener('input', function() {
                validateRequiredPoints();
            });
        }
    });
    
    function validateRequiredPoints() {
        let validPoints = 0;
        
        for (let i = 1; i <= 4; i++) {
            const lat = document.querySelector(`input[name="gps_lat_${i}"]`).value;
            const lng = document.querySelector(`input[name="gps_lng_${i}"]`).value;
            
            if (lat && lng) {
                validPoints++;
            }
        }
        
        // 最低4点の入力が必要
        if (validPoints < 4) {
            // エラーメッセージを表示する場合はここに追加
        }
    }
});
</script>
@endsection
