@extends('layouts.dashboard')

@section('title', '圃場管理')
@section('header-title', '圃場管理')

@section('content')
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 mx-4">
        {{ session('success') }}
    </div>
@endif

<!-- Google Maps Modal -->
<div id="mapModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
            <!-- Modal Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">圃場地図表示</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">
                    &times;
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <!-- 地図表示エリア -->
                <div>
                    <h4 class="text-lg font-semibold mb-3">地図表示</h4>
                    <div id="map" class="w-full h-96 bg-gray-200 rounded-lg"></div>
                </div>
                
                <div id="loading" class="hidden text-center py-4 text-gray-600">データを読み込み中...</div>
                <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4"></div>
            </div>
        </div>
    </div>
</div>
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- 検索フォーム -->
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('farm-management.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">栽培方法</label>
                        <input type="text" name="cultivation_method" value="{{ $input['cultivation_method'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="栽培方法（例：水田、畑）">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">作物種別</label>
                        <input type="text" name="crop_type" value="{{ $input['crop_type'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="作物種別（例：米、トマト）">
                    </div>
                </div>
                <div class="flex flex-row gap-4 mt-6">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        検索
                    </button>
                    <a href="{{ route('farm-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
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
                        <h2 class="text-xl font-semibold text-gray-800">圃場一覧</h2>
                        <p class="text-gray-600 mt-1">全{{ $farms->total() }}件中 {{ $farms->firstItem() ?? 0 }}-{{ $farms->lastItem() ?? 0 }}件を表示</p>
                    </div>
                    <a href="{{ route('farm-management.create') }}" 
                       class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        圃場を追加
                    </a>
                </div>
            </div>

            @if($farms->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">ID</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">農場名</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">栽培方法</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">作物種別</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">ユーザーID</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">作成日</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($farms as $farm)
                                <tr class="hover:bg-gray-50 cursor-pointer farm-row" data-farm-id="{{ $farm->id }}" data-farm-name="{{ $farm->farm_name }}">
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $farm->id }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $farm->farm_name }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $farm->cultivation_method }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $farm->crop_type }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $farm->app_user_id }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $farm->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- ページネーション -->
                <div class="px-8 py-6 border-t border-gray-200">
                    {{ $farms->appends($input)->links() }}
                </div>
            @else
                <div class="px-8 py-12 text-center">
                    <div class="text-gray-500 text-lg">検索条件に一致する圃場が見つかりませんでした。</div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Google Maps JavaScript -->
<script>
    // Google Maps APIキー
    const API_KEY = '{{ env('GOOGLE_MAPS_API_KEY') }}';
    
    // グローバル変数
    let map = null;
    let currentPolygon = null;
    let currentMarkers = [];
    let mapModal = null;
    
    // モーダル要素
    document.addEventListener('DOMContentLoaded', function() {
        mapModal = document.getElementById('mapModal');
        const closeModal = document.getElementById('closeModal');
        
        // モーダルを閉じる
        closeModal.addEventListener('click', function() {
            closeModalAndCleanup();
        });
        
        // モーダル外をクリックして閉じる
        mapModal.addEventListener('click', function(e) {
            if (e.target === mapModal) {
                closeModalAndCleanup();
            }
        });
        
        // モーダルを閉じてクリーンアップする関数
        function closeModalAndCleanup() {
            mapModal.classList.add('hidden');
            // 地図のオーバーレイをクリア
            clearOverlays();
        }
        
        // 圃場行のクリックイベント
        const farmRows = document.querySelectorAll('.farm-row');
        farmRows.forEach(row => {
            row.addEventListener('click', function() {
                const farmId = this.getAttribute('data-farm-id');
                const farmName = this.getAttribute('data-farm-name');
                
                // モーダルタイトルを更新
                document.getElementById('modalTitle').textContent = `${farmName} - 地図表示`;
                
                // モーダルを表示
                mapModal.classList.remove('hidden');
                
                // Google Mapsを初期化して圃場を表示
                showFarmOnMap(farmId);
            });
        });
    });
    
    // Google Maps APIの読み込み
    function loadGoogleMapsAPI() {
        return new Promise((resolve, reject) => {
            if (window.google && window.google.maps) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${API_KEY}&libraries=geometry`;
            script.async = true;
            script.defer = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    // 地図の初期化
    function initMap() {
        // デフォルトの中心座標（東京）
        const defaultCenter = { lat: 35.6762, lng: 139.6503 };
        
        map = new google.maps.Map(document.getElementById('map'), {
            center: defaultCenter,
            zoom: 10,
            mapTypeId: google.maps.MapTypeId.SATELLITE,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                position: google.maps.ControlPosition.TOP_RIGHT
            },
            streetViewControl: false,
            fullscreenControl: true
        });
    }
    
    // データ正規化: さまざまな形式のboundary_polygonを {lat, lng}[] に統一
    function normalizeBoundaryData(raw) {
        if (!raw) return [];
        let data = raw;
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (_) { /* keep as-is */ }
        }
        if (data && typeof data === 'object' && !Array.isArray(data) && data.boundary_polygon) {
            data = data.boundary_polygon;
        }
        if (!Array.isArray(data)) return [];
        const points = data.map((p) => {
            if (p && typeof p === 'object') {
                if (Object.prototype.hasOwnProperty.call(p, 'lat') && Object.prototype.hasOwnProperty.call(p, 'lng')) {
                    return { lat: parseFloat(p.lat), lng: parseFloat(p.lng) };
                }
                if (Object.prototype.hasOwnProperty.call(p, 'latitude') && Object.prototype.hasOwnProperty.call(p, 'longitude')) {
                    return { lat: parseFloat(p.latitude), lng: parseFloat(p.longitude) };
                }
            }
            if (Array.isArray(p) && p.length >= 2) {
                return { lat: parseFloat(p[0]), lng: parseFloat(p[1]) };
            }
            return null;
        }).filter(Boolean);
        return points;
    }

    function clearOverlays() {
        if (currentPolygon) {
            currentPolygon.setMap(null);
            currentPolygon = null;
        }
        if (currentMarkers && currentMarkers.length) {
            currentMarkers.forEach(m => m.setMap(null));
            currentMarkers = [];
        }
    }



    // 圃場を地図上に表示（まずは全点をマーカー表示）
    async function showFarmOnMap(farmId) {
        const loading = document.getElementById('loading');
        const errorMessage = document.getElementById('error-message');
        
        // ローディング表示
        loading.classList.remove('hidden');
        errorMessage.classList.add('hidden');
        
        try {
            // Google Maps APIを読み込み
            await loadGoogleMapsAPI();
            
            // 地図を初期化（初回のみ）
            if (!map) {
                initMap();
            }
            
            // APIから圃場データを取得
            const response = await fetch(`/api/farms/${farmId}/boundary`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'データの取得に失敗しました。');
            }
            
            if (!data.success) {
                throw new Error('データの取得に失敗しました。');
            }
            
            // 既存のオーバーレイをクリア
            clearOverlays();
            
            // 境界線データを取得
            const normalized = normalizeBoundaryData(data.data.boundary_polygon);
            
            if (!normalized || normalized.length === 0) {
                throw new Error('境界線データが正しく設定されていません。');
            }

            // 全点をマーカーとして表示
            normalized.forEach(coord => {
                const marker = new google.maps.Marker({ position: coord, map });
                currentMarkers.push(marker);
            });

            // 点群を結んでポリゴンを描画
            currentPolygon = new google.maps.Polygon({
                paths: normalized,
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: '#FF0000',
                fillOpacity: 0.25,
                map: map
            });

            // 表示範囲を合わせる（点群）
            const bounds = new google.maps.LatLngBounds();
            normalized.forEach(coord => bounds.extend(coord));
            if (normalized.length === 1) {
                map.setCenter(normalized[0]);
                map.setZoom(18);
            } else {
                map.fitBounds(bounds);
            }
            
        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = `エラー: ${error.message}`;
            errorMessage.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
        }
    }
</script>
@endsection
