@extends('layouts.dashboard')

@section('title', '圃場詳細')
@section('header-title')
圃場詳細 － {{ $farm->farm_name }}
@endsection

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 space-y-6">
        <a href="{{ route('farm-management.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">
            圃場一覧に戻る
        </a>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">圃場詳細 － {{ $farm->farm_name }}</h2>
                    <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-sm">
                        @if($isAdmin)
                            <div><span class="text-gray-500">ユーザー名</span> <span class="ml-2">{{ $farm->appUser?->name ?? '-' }}</span></div>
                        @endif
                        <div><span class="text-gray-500">栽培方法</span> <span class="ml-2">{{ $farm->cultivation_method ?: '-' }}</span></div>
                        <div><span class="text-gray-500">作物種別</span> <span class="ml-2">{{ $farm->crop_type ?: '-' }}</span></div>
                    </dl>
                    <div class="mt-2 flex gap-2">
                        @if($farm->hidden_at)
                            <span class="inline-flex rounded-full bg-gray-200 px-2 py-1 text-xs font-semibold text-gray-700">非表示中</span>
                        @endif
                        @if($farm->isProvisional())
                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">仮登録</span>
                        @endif
                    </div>
                </div>
                @can('manage', $farm)
                    <a href="{{ route('farm-management.edit', $farm) }}" class="text-blue-600 hover:text-blue-800 font-semibold">編集</a>
                @endcan
            </div>

            <form method="GET" action="{{ route('farm-management.show', $farm) }}" class="mt-6">
                <label for="measurement-date" class="block text-sm font-medium text-gray-700">測定日</label>
                <select id="measurement-date" name="date" onchange="this.form.submit()" class="mt-1 border rounded px-3 py-2">
                    @forelse($dates as $date)
                        <option value="{{ $date }}" @selected($date === $selectedDate)>{{ $date }}</option>
                    @empty
                        <option value="">測定日がありません</option>
                    @endforelse
                </select>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h3 class="text-lg font-semibold">推定値のヒートマップ</h3>
                <div class="flex flex-wrap gap-2" id="parameter-switch">
                    @foreach (['CEC', 'CaO', 'MgO', 'K2O'] as $parameter)
                        <button type="button" data-parameter="{{ $parameter }}"
                                class="parameter-button px-3 py-1 rounded-full border text-sm {{ $loop->first ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700' }}">
                            {{ $parameter }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div id="map" class="w-full h-[28rem] bg-gray-200 rounded-lg"></div>
            <p id="map-empty" class="hidden mt-3 text-sm text-gray-500">この測定日の地点がありません。</p>
            <div id="colorbar-container" class="mt-4 hidden">
                <div class="flex items-center justify-between mb-2">
                    <span id="colorbar-label" class="text-sm font-medium text-gray-700">CEC</span>
                    <span class="text-xs text-gray-500">低い値が青、中間が白、高い値が赤（モバイルの結果マップと同じ配色）</span>
                </div>
                <div id="colorbar" class="h-4 rounded border"></div>
                <div class="flex justify-between mt-1 text-xs text-gray-500">
                    <span id="colorbar-min"></span>
                    <span id="colorbar-max"></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">土壌分析レーダーチャート</h3>
            <div id="chart-placeholder" class="bg-gray-50 p-8 rounded-lg text-center text-gray-500">
                地図上の測定点をクリックすると、その地点のレーダーチャートを表示します。
            </div>
            <div id="chart-container" class="bg-gray-50 p-4 rounded-lg hidden" style="height: 360px;">
                <canvas id="radarChart"></canvas>
            </div>
            <div id="chart-info" class="mt-2 text-sm text-gray-600"></div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">推定値の時系列</h3>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                @foreach (['CEC', 'CaO', 'MgO', 'K2O'] as $parameter)
                    @php $item = $series[$parameter]; @endphp
                    <div>
                        <h4 class="font-semibold">{{ $parameter }}@if($item['unit']) <span class="text-sm font-normal text-gray-500">{{ $item['unit'] }}</span>@endif</h4>
                        @if($item['farm_average'] !== null)
                            <p class="text-sm text-gray-600">圃場平均 {{ $item['farm_average'] }}</p>
                        @endif
                        @if(count($item['points']) === 0)
                            <p class="mt-3 text-sm text-gray-500">測定データがありません。</p>
                        @else
                            <div class="mt-2 h-64"><canvas id="chart-{{ $parameter }}"></canvas></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div id="timeline" class="bg-white rounded-2xl shadow p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h3 class="text-lg font-semibold">タイムライン</h3>
            </div>

            @if($canManageWorkLogs)
                <form method="POST" action="{{ route('farm-management.work-logs.store', $farm) }}" class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-3 border rounded-lg p-4">
                    @csrf
                    @if($selectedDate)
                        <input type="hidden" name="return_date" value="{{ $selectedDate }}">
                    @endif
                    <div class="md:col-span-2 font-semibold">＋作業記録</div>
                    <label class="text-sm">作業種別
                        <select name="work_type" required class="mt-1 w-full border rounded px-2 py-1">
                            @foreach($workTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('work_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm">作業日
                        <input type="date" name="work_date" required value="{{ old('work_date', now()->timezone('Asia/Tokyo')->toDateString()) }}" class="mt-1 w-full border rounded px-2 py-1">
                    </label>
                    <label class="text-sm">タイトル
                        <input type="text" name="title" maxlength="128" value="{{ old('title') }}" class="mt-1 w-full border rounded px-2 py-1">
                    </label>
                    <label class="text-sm">量
                        <span class="mt-1 flex gap-2">
                            <input type="number" name="amount_value" min="0" step="any" value="{{ old('amount_value') }}" class="w-full border rounded px-2 py-1">
                            <input type="text" name="amount_unit" maxlength="16" placeholder="kg" value="{{ old('amount_unit') }}" class="w-24 border rounded px-2 py-1">
                        </span>
                    </label>
                    <label class="text-sm md:col-span-2">メモ
                        <textarea name="detail" rows="2" class="mt-1 w-full border rounded px-2 py-1">{{ old('detail') }}</textarea>
                    </label>
                    <div>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">追加する</button>
                    </div>
                </form>
            @endif

            @if(count($timeline['items']) === 0)
                <p class="text-sm text-gray-500">測定も作業記録もありません。</p>
            @else
                <ol class="space-y-4">
                    @foreach($timeline['items'] as $item)
                        <li class="border rounded-lg p-4">
                            @if($item['type'] === 'measurement')
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <a href="{{ route('farm-management.show', ['farm' => $farm, 'date' => substr((string) $item['date'], 0, 10)]) }}" class="font-semibold text-blue-700 hover:underline">
                                        {{ substr((string) $item['date'], 0, 10) }}
                                    </a>
                                    <span class="text-sm text-gray-500">{{ $item['measurement_source'] === 'manual' ? '手動入力' : 'センサー測定' }} / {{ $item['count_points'] }}地点</span>
                                </div>
                                <dl class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                                    @foreach($item['values'] as $name => $value)
                                        <div>
                                            <dt class="text-gray-500">{{ $name }}</dt>
                                            <dd>{{ $value['avg'] }} {{ $value['unit'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                                @if(($item['delta']['CEC'] ?? null) !== null)
                                    <p class="mt-2 text-sm text-gray-600">前回からの CEC 差分 {{ $item['delta']['CEC'] }}</p>
                                @endif
                            @else
                                @php $workDate = substr((string) $item['date'], 0, 10); @endphp
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <div class="font-semibold">{{ $workDate }} {{ $workTypes[$item['work_type']] ?? $item['work_type'] }}</div>
                                    @if($item['title'])<div class="text-sm">{{ $item['title'] }}</div>@endif
                                </div>
                                @if($item['amount_value'] !== null)
                                    <p class="mt-1 text-sm text-gray-700">{{ $item['amount_value'] }} {{ $item['amount_unit'] }}</p>
                                @endif
                                @if($item['detail'])
                                    <p class="mt-1 text-sm text-gray-600 whitespace-pre-wrap">{{ $item['detail'] }}</p>
                                @endif
                                @if($canManageWorkLogs)
                                    <div class="mt-3 flex flex-wrap gap-4">
                                        <details>
                                            <summary class="cursor-pointer text-sm text-blue-700">編集</summary>
                                            <form method="POST" action="{{ route('farm-management.work-logs.update', [$farm, $item['id']]) }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                                @csrf
                                                @method('PUT')
                                                @if($selectedDate)<input type="hidden" name="return_date" value="{{ $selectedDate }}">@endif
                                                <label class="text-sm">作業種別
                                                    <select name="work_type" class="mt-1 w-full border rounded px-2 py-1">
                                                        @foreach($workTypes as $value => $label)
                                                            <option value="{{ $value }}" @selected($item['work_type'] === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="text-sm">作業日
                                                    <input type="date" name="work_date" required value="{{ $workDate }}" class="mt-1 w-full border rounded px-2 py-1">
                                                </label>
                                                <label class="text-sm">タイトル
                                                    <input type="text" name="title" maxlength="128" value="{{ $item['title'] }}" class="mt-1 w-full border rounded px-2 py-1">
                                                </label>
                                                <label class="text-sm">量
                                                    <span class="mt-1 flex gap-2">
                                                        <input type="number" name="amount_value" min="0" step="any" value="{{ $item['amount_value'] }}" class="w-full border rounded px-2 py-1">
                                                        <input type="text" name="amount_unit" maxlength="16" value="{{ $item['amount_unit'] }}" class="w-24 border rounded px-2 py-1">
                                                    </span>
                                                </label>
                                                <label class="text-sm md:col-span-2">メモ
                                                    <textarea name="detail" rows="2" class="mt-1 w-full border rounded px-2 py-1">{{ $item['detail'] }}</textarea>
                                                </label>
                                                <div><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">保存</button></div>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('farm-management.work-logs.destroy', [$farm, $item['id']]) }}" onsubmit="return confirm('この作業記録を削除します。よろしいですか？');">
                                            @csrf
                                            @method('DELETE')
                                            @if($selectedDate)<input type="hidden" name="return_date" value="{{ $selectedDate }}">@endif
                                            <button type="submit" class="text-sm text-red-600">削除</button>
                                        </form>
                                    </div>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const API_KEY = @json(env('GOOGLE_MAPS_API_KEY'));
    const points = @json($points);
    const boundary = @json($boundary);
    const series = @json($series);
    const measurementsUrl = @json(route('measurements.index'));
    const PARAMETERS = ['CEC', 'CaO', 'MgO', 'K2O'];

    let map = null;
    let polygon = null;
    let markers = [];
    let heatmap = null;
    let infoWindow = null;
    let radarChart = null;
    let parameter = 'CEC';

    function loadGoogleMaps() {
        return new Promise((resolve, reject) => {
            if (window.google && window.google.maps) {
                resolve();
                return;
            }
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${API_KEY}&libraries=geometry`;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function valueOf(point, name) {
        const found = (point.values || []).find((item) => item.parameter === name);
        if (!found || found.value === null || found.value === undefined || Number.isNaN(Number(found.value))) {
            return null;
        }
        return Number(found.value);
    }

    function hexToRgb(hex) {
        const n = parseInt(hex.slice(1), 16);
        return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }

    function lerp(a, b, t) {
        return a.map((value, index) => Math.round(value + (b[index] - value) * t));
    }

    function heatmapRgb(value, min, max) {
        if (value === null || Number.isNaN(value)) return [189, 189, 189];
        if (!(max > min)) return [255, 255, 255];
        const t = Math.min(1, Math.max(0, (value - min) / (max - min)));
        if (t <= 0.5) return lerp(hexToRgb('#1565C0'), hexToRgb('#FFFFFF'), t * 2);
        return lerp(hexToRgb('#FFFFFF'), hexToRgb('#C62828'), (t - 0.5) * 2);
    }

    function heatmapCss(value, min, max) {
        const [r, g, b] = heatmapRgb(value, min, max);
        return `rgb(${r}, ${g}, ${b})`;
    }

    function haversine(lat1, lng1, lat2, lng2) {
        const radius = 6371000;
        const p1 = lat1 * Math.PI / 180;
        const p2 = lat2 * Math.PI / 180;
        const dPhi = (lat2 - lat1) * Math.PI / 180;
        const dLambda = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dPhi / 2) ** 2 + Math.cos(p1) * Math.cos(p2) * Math.sin(dLambda / 2) ** 2;
        return 2 * radius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function idw(lat, lng, samples) {
        let numerator = 0;
        let denominator = 0;
        for (const sample of samples) {
            const distance = haversine(lat, lng, sample.lat, sample.lng);
            if (distance < 1) return sample.value;
            const weight = 1 / (distance * distance);
            numerator += weight * sample.value;
            denominator += weight;
        }
        return denominator === 0 ? null : numerator / denominator;
    }

    function insidePolygon(lat, lng, ring) {
        let hit = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const yi = ring[i].lat;
            const xi = ring[i].lng;
            const yj = ring[j].lat;
            const xj = ring[j].lng;
            const intersect = ((yi > lat) !== (yj > lat)) && (lng < (xj - xi) * (lat - yi) / ((yj - yi) || 1e-12) + xi);
            if (intersect) hit = !hit;
        }
        return hit;
    }

    function samplesFor(name) {
        return points
            .filter((point) => typeof point.lat === 'number' && typeof point.lng === 'number' && valueOf(point, name) !== null)
            .map((point) => ({ lat: point.lat, lng: point.lng, value: valueOf(point, name) }));
    }

    function buildHeatmapCanvas(samples, bounds, ring) {
        const size = 80;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        const image = context.createImageData(size, size);
        const values = samples.map((sample) => sample.value);
        const min = Math.min(...values);
        const max = Math.max(...values);
        const south = bounds.getSouthWest().lat();
        const west = bounds.getSouthWest().lng();
        const north = bounds.getNorthEast().lat();
        const east = bounds.getNorthEast().lng();
        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                const lat = north - (north - south) * ((y + 0.5) / size);
                const lng = west + (east - west) * ((x + 0.5) / size);
                const index = (y * size + x) * 4;
                if (ring.length >= 3 && !insidePolygon(lat, lng, ring)) continue;
                if (ring.length < 3) {
                    let nearest = Infinity;
                    for (const sample of samples) {
                        nearest = Math.min(nearest, haversine(lat, lng, sample.lat, sample.lng));
                    }
                    if (nearest > 40) continue;
                }
                const [r, g, b] = heatmapRgb(idw(lat, lng, samples), min, max);
                image.data[index] = r;
                image.data[index + 1] = g;
                image.data[index + 2] = b;
                image.data[index + 3] = 150;
            }
        }
        context.putImageData(image, 0, 0);
        return canvas;
    }

    function attachOverlay(canvas, bounds) {
        const overlay = new google.maps.OverlayView();
        overlay.onAdd = function () {
            canvas.style.position = 'absolute';
            canvas.style.pointerEvents = 'none';
            this.getPanes().overlayLayer.appendChild(canvas);
        };
        overlay.draw = function () {
            const projection = this.getProjection();
            const sw = projection.fromLatLngToDivPixel(bounds.getSouthWest());
            const ne = projection.fromLatLngToDivPixel(bounds.getNorthEast());
            if (!sw || !ne) return;
            canvas.style.left = `${Math.min(sw.x, ne.x)}px`;
            canvas.style.top = `${Math.min(sw.y, ne.y)}px`;
            canvas.style.width = `${Math.abs(ne.x - sw.x)}px`;
            canvas.style.height = `${Math.abs(sw.y - ne.y)}px`;
        };
        overlay.onRemove = function () {
            canvas.remove();
        };
        overlay.setMap(map);
        return overlay;
    }

    function boundsOf(coords) {
        const bounds = new google.maps.LatLngBounds();
        coords.forEach((coord) => bounds.extend(coord));
        if (coords.length === 1) {
            const coord = coords[0];
            bounds.extend({ lat: coord.lat + 0.0004, lng: coord.lng + 0.0004 });
            bounds.extend({ lat: coord.lat - 0.0004, lng: coord.lng - 0.0004 });
        }
        return bounds;
    }

    function updateColorbar(samples) {
        const container = document.getElementById('colorbar-container');
        if (samples.length === 0) {
            container.classList.add('hidden');
            return;
        }
        const values = samples.map((sample) => sample.value);
        const min = Math.min(...values);
        const max = Math.max(...values);
        const stops = [];
        for (let i = 0; i <= 20; i++) {
            const value = min + (max - min) * (i / 20);
            stops.push(`${heatmapCss(value, min, max)} ${(i / 20) * 100}%`);
        }
        document.getElementById('colorbar').style.background = `linear-gradient(to right, ${stops.join(', ')})`;
        document.getElementById('colorbar-label').textContent = parameter;
        document.getElementById('colorbar-min').textContent = min.toFixed(1);
        document.getElementById('colorbar-max').textContent = max.toFixed(1);
        container.classList.remove('hidden');
    }

    function clearLayers() {
        markers.forEach((marker) => marker.setMap(null));
        markers = [];
        if (heatmap) {
            heatmap.setMap(null);
            heatmap = null;
        }
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }

    function showRadar(point) {
        const placeholder = document.getElementById('chart-placeholder');
        const container = document.getElementById('chart-container');
        const info = document.getElementById('chart-info');
        const cec = valueOf(point, 'CEC');
        const numberText = point.measurement_number === null || point.measurement_number === undefined ? '番号なし' : `測定番号 ${point.measurement_number}`;
        if (cec === null || cec === 0) {
            placeholder.classList.remove('hidden');
            container.classList.add('hidden');
            info.textContent = `${numberText}: この地点には有効なCECデータがありません。`;
            return;
        }
        const saturation = (name) => {
            const value = valueOf(point, name);
            return value === null ? 0 : (value / cec) * 100;
        };
        const k2o = saturation('K2O');
        const cao = saturation('CaO');
        const mgo = saturation('MgO');
        placeholder.classList.add('hidden');
        container.classList.remove('hidden');
        if (radarChart) radarChart.destroy();
        radarChart = new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: ['K2O飽和度', 'CaO飽和度', 'MgO飽和度'],
                datasets: [{
                    data: [k2o, cao, mgo],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { r: { min: 0, max: 100, ticks: { stepSize: 20 } } },
            },
        });
        info.textContent = `${numberText}  K2O ${k2o.toFixed(1)}% / CaO ${cao.toFixed(1)}% / MgO ${mgo.toFixed(1)}%（CEC ${cec}）`;
    }

    function drawParameter() {
        clearLayers();
        const samples = samplesFor(parameter);
        document.getElementById('map-empty').classList.toggle('hidden', points.length > 0);
        updateColorbar(samples);
        if (samples.length > 0) {
            const coords = boundary.length >= 3 ? boundary : samples;
            const bounds = boundsOf(coords);
            heatmap = attachOverlay(buildHeatmapCanvas(samples, bounds, boundary), bounds);
        }
        const values = samples.map((sample) => sample.value);
        const min = values.length ? Math.min(...values) : 0;
        const max = values.length ? Math.max(...values) : 0;
        if (!infoWindow) infoWindow = new google.maps.InfoWindow();
        points.forEach((point) => {
            if (typeof point.lat !== 'number' || typeof point.lng !== 'number') return;
            const value = valueOf(point, parameter);
            const color = heatmapRgb(value, min, max);
            const hasNumber = point.measurement_number !== null && point.measurement_number !== undefined;
            const marker = new google.maps.Marker({
                position: { lat: point.lat, lng: point.lng },
                map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 16,
                    fillColor: heatmapCss(value, min, max),
                    fillOpacity: 0.95,
                    strokeColor: '#333333',
                    strokeWeight: 1,
                },
                label: hasNumber ? {
                    text: String(point.measurement_number),
                    color: (0.299 * color[0] + 0.587 * color[1] + 0.114 * color[2]) / 255 > 0.62 ? '#111111' : '#ffffff',
                    fontSize: '12px',
                    fontWeight: 'bold',
                } : undefined,
                zIndex: 2,
            });
            marker.addListener('click', () => {
                const rows = (point.values || []).map((item) => {
                    const unit = item.unit ? ` ${esc(item.unit)}` : '';
                    const shown = item.value === null || item.value === undefined ? '—' : esc(item.value);
                    return `<tr><td class="pr-3">${esc(item.parameter)}</td><td>${shown}${unit}</td></tr>`;
                }).join('');
                const numberText = hasNumber ? esc(point.measurement_number) : '—';
                infoWindow.setContent(
                    `<div class="text-sm"><div class="font-bold mb-1">測定番号 ${numberText}</div><table>${rows}</table>` +
                    `<div class="mt-2"><a class="text-blue-700 underline" href="${measurementsUrl}?open=${encodeURIComponent(point.upload_id)}">測定の詳細</a></div></div>`
                );
                infoWindow.open({ map, anchor: marker });
                showRadar(point);
            });
            markers.push(marker);
        });
    }

    function drawCharts() {
        PARAMETERS.forEach((name) => {
            const data = series[name];
            const canvas = document.getElementById(`chart-${name}`);
            if (!canvas || !data || !data.points.length) return;
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.points.map((point) => point.date),
                    datasets: [
                        { label: '平均', data: data.points.map((point) => point.avg), borderColor: '#1565C0', tension: 0.2 },
                        { label: '最小', data: data.points.map((point) => point.min), borderColor: '#90A4AE', borderDash: [4, 4] },
                        { label: '最大', data: data.points.map((point) => point.max), borderColor: '#C62828', borderDash: [4, 4] },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        });
    }

    document.getElementById('parameter-switch').addEventListener('click', (event) => {
        const button = event.target.closest('[data-parameter]');
        if (!button || !map) return;
        parameter = button.dataset.parameter;
        document.querySelectorAll('.parameter-button').forEach((item) => {
            const active = item.dataset.parameter === parameter;
            item.classList.toggle('bg-blue-600', active);
            item.classList.toggle('text-white', active);
            item.classList.toggle('border-blue-600', active);
            item.classList.toggle('bg-white', !active);
            item.classList.toggle('text-gray-700', !active);
        });
        drawParameter();
    });

    document.addEventListener('DOMContentLoaded', async () => {
        drawCharts();
        try {
            await loadGoogleMaps();
            const center = boundary[0] || points.find((point) => typeof point.lat === 'number') || { lat: 35.6812, lng: 139.7671 };
            map = new google.maps.Map(document.getElementById('map'), {
                center,
                zoom: 16,
                mapTypeId: google.maps.MapTypeId.SATELLITE,
                streetViewControl: false,
            });
            if (boundary.length) {
                polygon = new google.maps.Polygon({
                    paths: boundary,
                    strokeColor: '#1565C0',
                    strokeWeight: 2,
                    fillOpacity: 0,
                    map,
                });
            }
            const fit = [];
            boundary.forEach((coord) => fit.push(coord));
            points.forEach((point) => {
                if (typeof point.lat === 'number' && typeof point.lng === 'number') fit.push({ lat: point.lat, lng: point.lng });
            });
            if (fit.length) {
                const bounds = boundsOf(fit);
                if (fit.length === 1) {
                    map.setCenter(fit[0]);
                    map.setZoom(18);
                } else {
                    map.fitBounds(bounds);
                }
            }
            drawParameter();
        } catch (error) {
            document.getElementById('map-empty').textContent = '地図を読み込めませんでした。';
            document.getElementById('map-empty').classList.remove('hidden');
        }
    });
})();
</script>
@endsection
