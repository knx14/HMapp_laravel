@extends('layouts.dashboard')

@section('title', 'CEC結果表示')
@section('header-title', 'CEC結果表示')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- 戻るボタン -->
        <div class="mb-4">
            <a href="{{ route('estimation-results.farm-dates', ['farm' => $farm->id]) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                日付選択に戻る
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-2">圃場情報</h2>
            <p class="text-gray-700"><span class="font-semibold">圃場ID:</span> {{ $farm->id }}</p>
            <p class="text-gray-700"><span class="font-semibold">農場名:</span> {{ $farm->farm_name }}</p>
            <p class="text-gray-700"><span class="font-semibold">測定日:</span> {{ $upload->measurement_date }}</p>
        </div>

        <!-- 地図表示エリア -->
        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Googleマップ（CEC値ヒートマップ）</h3>
            <div id="map" class="w-full h-96 bg-gray-200 rounded-lg"></div>
            <div id="loading" class="hidden text-center py-4 text-gray-600">地図を読み込み中...</div>
            <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4"></div>
            
            <!-- ヒートマップカラーバー -->
            <div id="colorbar-container" class="mt-4 hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">CEC値ヒートマップ</span>
                    <span id="average-cec" class="text-sm text-gray-600"></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="text-xs text-gray-600">低</div>
                    <div id="colorbar" class="flex-1 h-4 rounded border"></div>
                    <div class="text-xs text-gray-600">高</div>
                </div>
                <div class="flex justify-between mt-1">
                    <span id="min-cec" class="text-xs text-gray-500"></span>
                    <span id="max-cec" class="text-xs text-gray-500"></span>
                </div>
            </div>
        </div>
        
        <!-- レーダーチャート表示エリア -->
        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">土壌分析レーダーチャート</h3>
            <div id="chart-placeholder" class="bg-gray-50 p-8 rounded-lg text-center text-gray-500">
                <div class="text-lg mb-2">📍</div>
                <div>地図上の地点をクリックして、その地点のレーダーチャートを表示してください</div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg hidden" id="chart-container">
                <canvas id="radarChart" width="400" height="400"></canvas>
            </div>
            <div id="chart-info" class="mt-2 text-sm text-gray-600"></div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const API_KEY = '{{ env('GOOGLE_MAPS_API_KEY') }}';

    const boundaryPolygonRaw = @json($boundaryPolygon);
    const pointsRaw = @json($points);

    let map = null;
    let currentPolygon = null;
    let currentMarkers = [];
    let radarChart = null;
    let cecStats = null; // CEC値の統計情報

    function normalizeBoundaryData(raw) {
        if (!raw) return [];
        let data = raw;
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (_) {}
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


    function clearOverlays() {
        if (currentPolygon) { currentPolygon.setMap(null); currentPolygon = null; }
        if (currentMarkers && currentMarkers.length) { currentMarkers.forEach(m => m.setMap(null)); currentMarkers = []; }
    }

    // CEC値の統計情報を計算
    function calculateCecStats() {
        const cecValues = pointsRaw
            .map(p => p.cec)
            .filter(cec => cec !== null && cec !== undefined && !isNaN(cec))
            .sort((a, b) => a - b);
        
        if (cecValues.length === 0) {
            return null;
        }
        
        const min = cecValues[0];
        const max = cecValues[cecValues.length - 1];
        const average = cecValues.reduce((sum, val) => sum + val, 0) / cecValues.length;
        
        return { min, max, average, values: cecValues };
    }

    // CEC値に基づいてヒートマップ色を計算
    function getHeatmapColor(cecValue) {
        if (!cecStats || cecValue === null || cecValue === undefined || isNaN(cecValue)) {
            return '#808080'; // グレー（データなし）
        }
        
        const { min, max } = cecStats;
        if (min === max) {
            return '#4A90E2'; // 平均的な青色
        }
        
        // 0-1の範囲に正規化
        const normalized = (cecValue - min) / (max - min);
        
        // ヒートマップカラー（青→緑→黄→赤）
        if (normalized <= 0.25) {
            // 青系（低い値）
            const intensity = normalized * 4;
            return `rgb(${Math.round(74 + intensity * 181)}, ${Math.round(144 + intensity * 111)}, ${Math.round(226 - intensity * 226)})`;
        } else if (normalized <= 0.5) {
            // 青→緑
            const intensity = (normalized - 0.25) * 4;
            return `rgb(${Math.round(255 - intensity * 255)}, ${Math.round(255)}, ${Math.round(0 + intensity * 255)})`;
        } else if (normalized <= 0.75) {
            // 緑→黄
            const intensity = (normalized - 0.5) * 4;
            return `rgb(${Math.round(255 - intensity * 255)}, ${Math.round(255 - intensity * 255)}, ${Math.round(255)})`;
        } else {
            // 黄→赤
            const intensity = (normalized - 0.75) * 4;
            return `rgb(${Math.round(255)}, ${Math.round(255 - intensity * 255)}, ${Math.round(255 - intensity * 255)})`;
        }
    }

    // カラーバーを生成
    function createColorBar() {
        const colorbar = document.getElementById('colorbar');
        const minCec = document.getElementById('min-cec');
        const maxCec = document.getElementById('max-cec');
        const averageCec = document.getElementById('average-cec');
        const container = document.getElementById('colorbar-container');
        
        if (!cecStats) return;
        
        // グラデーションを生成
        const steps = 100;
        let gradient = 'linear-gradient(to right, ';
        for (let i = 0; i <= steps; i++) {
            const normalized = i / steps;
            const cecValue = cecStats.min + (cecStats.max - cecStats.min) * normalized;
            const color = getHeatmapColor(cecValue);
            gradient += `${color} ${(i / steps) * 100}%`;
            if (i < steps) gradient += ', ';
        }
        gradient += ')';
        
        colorbar.style.background = gradient;
        minCec.textContent = cecStats.min.toFixed(1);
        maxCec.textContent = cecStats.max.toFixed(1);
        averageCec.textContent = `平均: ${cecStats.average.toFixed(1)} meq/100g`;
        container.classList.remove('hidden');
    }

    async function showFarmBoundaryAndPoints() {
        const loading = document.getElementById('loading');
        const errorMessage = document.getElementById('error-message');
        loading.classList.remove('hidden');
        errorMessage.classList.add('hidden');
        try {
            await loadGoogleMapsAPI();
            
            // 地図がまだ初期化されていない場合は初期化
            if (!map) {
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

            clearOverlays();

            const normalizedBoundary = normalizeBoundaryData(boundaryPolygonRaw);
            if (normalizedBoundary && normalizedBoundary.length) {
                currentPolygon = new google.maps.Polygon({
                    paths: normalizedBoundary,
                    strokeColor: '#FF0000', strokeOpacity: 0.8, strokeWeight: 3,
                    fillColor: '#FF0000', fillOpacity: 0.25, map
                });

                const bounds = new google.maps.LatLngBounds();
                normalizedBoundary.forEach(coord => bounds.extend(coord));
                map.fitBounds(bounds);
            }

            // CEC値の統計情報を計算
            cecStats = calculateCecStats();
            
            // カラーバーを生成
            createColorBar();
            
            // ポイントをマーカーで表示（ヒートマップ色、クリックで全成分とレーダーチャート）
            const info = new google.maps.InfoWindow();
            pointsRaw.forEach((p, index) => {
                if (typeof p.lat !== 'number' || typeof p.lng !== 'number') return;
                
                const cecValue = p.cec ?? 0;
                const heatmapColor = getHeatmapColor(cecValue);
                const hasNumber = p.measurement_number !== null && p.measurement_number !== undefined;
                
                // カスタムマーカーアイコンを作成
                const markerIcon = {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 20,
                    fillColor: heatmapColor,
                    fillOpacity: 0.8,
                    strokeColor: '#ffffff',
                    strokeWeight: 2
                };
                
                const marker = new google.maps.Marker({
                    position: { lat: p.lat, lng: p.lng },
                    map,
                    icon: markerIcon,
                    label: hasNumber ? { 
                        text: String(p.measurement_number), 
                        className: 'cec-heatmap-label',
                        color: '#ffffff',
                        fontSize: '12px',
                        fontWeight: 'bold'
                    } : undefined
                });
                
                marker.addListener('click', () => {
                    // 情報ウィンドウを表示
                    const rows = (p.values || []).map(v => {
                        const unitText = v.unit ? ' ' + v.unit : '';
                        return `<tr><td class="pr-4 py-0.5 text-gray-700">${v.parameter}</td><td class="text-gray-900 font-semibold">${v.value}${unitText}</td></tr>`;
                    }).join('');
                    const numberText = hasNumber ? p.measurement_number : '—';
                    const cecText = p.cec === null || p.cec === undefined ? '—' : Number(p.cec).toFixed(1);
                    const html = `<div class="p-1"><div class="font-bold mb-1">測定番号 ${numberText}</div><div class="text-xs text-gray-600 mb-1">CEC ${cecText}</div><table>${rows}</table><div class="mt-2 text-xs text-blue-600">💡 下のレーダーチャートで詳細表示</div></div>`;
                    info.setContent(html);
                    info.open({ map, anchor: marker });
                    
                    // 該当地点のレーダーチャートを表示
                    showRadarChartForPoint(index);
                });
                currentMarkers.push(marker);
            });

        } catch (e) {
            console.error('Map initialization error:', e);
            errorMessage.textContent = `地図の初期化エラー: ${e.message || e}`;
            errorMessage.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
        }
    }

    // CECに対する飽和度を計算する関数
    // value: 成分の値（meq/100g）
    // cec: CEC値（meq/100g）
    function calculateSaturation(value, cec) {
        if (cec === 0) return 0; // ゼロ除算を避ける
        
        // 飽和度(%) = (meq / CEC) × 100
        return (value / cec) * 100;
    }

    // 指定された地点のレーダーチャートを表示する関数
    function showRadarChartForPoint(pointIndex) {
        const chartInfo = document.getElementById('chart-info');
        const chartPlaceholder = document.getElementById('chart-placeholder');
        const chartContainer = document.getElementById('chart-container');
        
        try {
            if (!pointsRaw || pointsRaw.length === 0) {
                chartInfo.textContent = '測定データがありません。';
                return;
            }
            
            if (pointIndex < 0 || pointIndex >= pointsRaw.length) {
                chartInfo.textContent = '無効な地点インデックスです。';
                return;
            }
            
            const point = pointsRaw[pointIndex];
            const cec = point.cec || 0;
            const k2oValue = point.values?.find(v => v.parameter === 'K2O');
            const caoValue = point.values?.find(v => v.parameter === 'CaO');
            const mgoValue = point.values?.find(v => v.parameter === 'MgO');
            
            const k2o = k2oValue?.value || 0;
            const k2oUnit = k2oValue?.unit || null;
            const cao = caoValue?.value || 0;
            const caoUnit = caoValue?.unit || null;
            const mgo = mgoValue?.value || 0;
            const mgoUnit = mgoValue?.unit || null;
            
            if (cec <= 0) {
                chartInfo.textContent = 'この地点には有効なCECデータがありません。';
                chartPlaceholder.classList.remove('hidden');
                chartContainer.classList.add('hidden');
                return;
            }
            
            // プレースホルダーを非表示、チャートコンテナを表示
            chartPlaceholder.classList.add('hidden');
            chartContainer.classList.remove('hidden');
            
            // 既存のチャートを破棄
            if (radarChart) {
                radarChart.destroy();
            }
            
            // レーダーチャートを作成
            const ctx = document.getElementById('radarChart').getContext('2d');
            radarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['K2O飽和度', 'CaO飽和度', 'MgO飽和度'],
                    datasets: [{
                        label: point.measurement_number != null ? `地点${point.measurement_number}` : '地点',
                        data: [
                            calculateSaturation(k2o, cec),
                            calculateSaturation(cao, cec),
                            calculateSaturation(mgo, cec)
                        ],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        pointBackgroundColor: 'rgb(59, 130, 246)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            min: 0,
                            ticks: {
                                stepSize: 20,
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            pointLabels: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false // 単一地点なので凡例は非表示
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed.r.toFixed(1) + '%';
                                }
                            }
                        }
                    }
                }
            });
            
            // 詳細情報を表示
            const k2oSat = calculateSaturation(k2o, cec).toFixed(1);
            const caoSat = calculateSaturation(cao, cec).toFixed(1);
            const mgoSat = calculateSaturation(mgo, cec).toFixed(1);
            
            chartInfo.innerHTML = `
                <strong>地点${pointIndex + 1}</strong> の土壌分析結果（CECに対する飽和度）<br>
                K2O: ${k2oSat}% | CaO: ${caoSat}% | MgO: ${mgoSat}%<br>
                <small class="text-gray-500">CEC: ${cec} meq/100g</small><br>
                <small class="text-gray-400">※ 飽和度 = (成分のmeq/100g / CEC) × 100</small>
            `;
            
        } catch (error) {
            console.error('Radar chart error:', error);
            chartInfo.textContent = `エラー: ${error.message}`;
            chartPlaceholder.classList.remove('hidden');
            chartContainer.classList.add('hidden');
        }
    }

    // グローバル関数として定義（Google Maps APIのコールバック用）
    window.initMap = function() {
        console.log('Google Maps API loaded successfully');
    };

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing map...');
        showFarmBoundaryAndPoints();
    });
</script>

<style>
.cec-label{background:#2563eb;color:#fff;padding:2px 4px;border-radius:4px;border:1px solid rgba(0,0,0,0.2)}
.cec-heatmap-label{
    background: rgba(0,0,0,0.6);
    color: #fff;
    padding: 2px 6px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.8);
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
}
</style>
@endsection


