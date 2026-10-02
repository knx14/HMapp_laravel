@extends('layouts.dashboard')

@php
    $isEdit = $farm !== null;
    $pageTitle = $isEdit ? '圃場編集' : '圃場登録';
    $initialBoundary = old('boundary_polygon', json_encode($boundary));
    $selectedOwner = (string) old('app_user_id', $farm?->app_user_id ?? '');
@endphp

@section('title', $pageTitle)
@section('header-title', $pageTitle)

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto px-4">
        <div class="mb-6">
            <a href="{{ route('farm-management.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                圃場管理に戻る
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">{{ $isEdit ? '圃場を編集' : '新しい圃場を登録' }}</h2>

            <form id="farmForm" method="POST"
                  action="{{ $isEdit ? route('farm-management.update', $farm) : route('farm-management.store') }}"
                  class="space-y-6">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                @can('admin')
                    <div>
                        <label for="app_user_id" class="block text-sm font-semibold text-gray-700 mb-2">
                            所有者 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="ownerFilter"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="名前・メールアドレス・所属で絞り込み">
                        <select id="app_user_id" name="app_user_id" required
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('app_user_id') border-red-500 @enderror">
                            <option value="">選択してください</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}"
                                        data-search="{{ mb_strtolower($owner->name.' '.$owner->email.' '.$owner->organization) }}"
                                        @selected($selectedOwner === (string) $owner->id)>
                                    {{ $owner->name }}（{{ $owner->email }}{{ $owner->organization ? ' / '.$owner->organization : '' }}）
                                </option>
                            @endforeach
                        </select>
                        @if($isEdit)
                            <p class="mt-1 text-sm text-gray-500">所有者を変更すると、この圃場の測定データも新しい所有者の圃場として扱われます。</p>
                        @endif
                    </div>
                @endcan

                <div>
                    <label for="farm_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        圃場名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="farm_name" name="farm_name" required maxlength="255"
                           value="{{ old('farm_name', $farm?->farm_name) }}"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('farm_name') border-red-500 @enderror"
                           placeholder="圃場名を入力してください">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="cultivation_method" class="block text-sm font-semibold text-gray-700 mb-2">栽培方法</label>
                        <input type="text" id="cultivation_method" name="cultivation_method" maxlength="255"
                               value="{{ old('cultivation_method', $farm?->cultivation_method) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="例：水田、畑">
                    </div>
                    <div>
                        <label for="crop_type" class="block text-sm font-semibold text-gray-700 mb-2">作物種別</label>
                        <input type="text" id="crop_type" name="crop_type" maxlength="255"
                               value="{{ old('crop_type', $farm?->crop_type) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="例：米、トマト">
                    </div>
                </div>

                <div>
                    <div class="flex flex-wrap justify-between items-end gap-2 mb-2">
                        <div>
                            <span class="block text-sm font-semibold text-gray-700">圃場の境界</span>
                            <p class="text-sm text-gray-500">地図をクリックして頂点を追加します（4点以上）。頂点はドラッグで移動できます。境界を描かずに保存すると仮登録になります。</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" id="currentLocationBtn" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-sm font-semibold py-1 px-3 rounded">現在地へ移動</button>
                            <button type="button" id="undoPointBtn" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-sm font-semibold py-1 px-3 rounded">1点戻す</button>
                            <button type="button" id="clearPointsBtn" class="bg-white border border-red-300 hover:bg-red-50 text-red-600 text-sm font-semibold py-1 px-3 rounded">境界をクリア</button>
                        </div>
                    </div>
                    <div id="boundaryMap" class="relative w-full h-[28rem] overflow-hidden bg-gray-200 rounded-lg"></div>
                    <p id="boundaryStatus" class="mt-2 text-sm text-gray-600"></p>
                    <p id="mapError" class="hidden mt-2 text-sm text-red-600"></p>
                    <input type="hidden" id="boundary_polygon" name="boundary_polygon" value="{{ $initialBoundary }}">
                </div>

                <div class="flex flex-wrap justify-between items-center gap-4 pt-6 border-t border-gray-200">
                    <div class="flex gap-4">
                        <a href="{{ route('farm-management.index') }}" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-3 px-8 rounded-lg transition">
                            キャンセル
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition">
                            {{ $isEdit ? '更新する' : '登録する' }}
                        </button>
                    </div>
                    @if($isEdit)
                        <button type="submit" form="deleteFarmForm" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg transition">
                            削除する
                        </button>
                    @endif
                </div>
            </form>

            @if($isEdit)
                <form id="deleteFarmForm" method="POST" action="{{ route('farm-management.destroy', $farm) }}"
                      data-confirm="「{{ $farm->farm_name }}」を削除します。測定・作業記録がある場合はデータを残して非表示になります。よろしいですか？"
                      onsubmit="return confirm(this.dataset.confirm);">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>
    </div>
</div>

<script>
    const API_KEY = @json(config('services.google.maps_api_key'));
    const MIN_POINTS = 4;

    document.addEventListener('DOMContentLoaded', function() {
        const ownerFilter = document.getElementById('ownerFilter');
        if (ownerFilter) {
            const select = document.getElementById('app_user_id');
            ownerFilter.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                Array.from(select.options).forEach(option => {
                    if (!option.value) return;
                    option.hidden = keyword !== '' && !option.dataset.search.includes(keyword) && !option.selected;
                });
            });
        }

        initBoundaryEditor().catch(error => {
            console.error(error);
            const mapError = document.getElementById('mapError');
            mapError.textContent = '地図を読み込めませんでした。境界なしで保存すると仮登録になります。';
            mapError.classList.remove('hidden');
        });
    });

    function loadGoogleMapsAPI() {
        return new Promise((resolve, reject) => {
            if (!API_KEY) {
                reject(new Error('missing api key'));
                return;
            }
            if (window.google && window.google.maps && window.google.maps.Map) {
                resolve();
                return;
            }
            const callback = `hmMapsReady_${Date.now()}`;
            window[callback] = async () => {
                delete window[callback];
                try {
                    if (google.maps.importLibrary) {
                        await google.maps.importLibrary('maps');
                        await google.maps.importLibrary('geometry');
                    }
                    resolve();
                } catch (error) {
                    reject(error);
                }
            };
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(API_KEY)}&libraries=geometry&loading=async&callback=${callback}`;
            script.async = true;
            script.onerror = () => {
                delete window[callback];
                reject(new Error('map script'));
            };
            document.head.appendChild(script);
        });
    }

    function parseInitialBoundary(value) {
        try {
            const data = JSON.parse(value || '[]');
            if (!Array.isArray(data)) return [];
            return data
                .map(p => ({ lat: parseFloat(p.lat), lng: parseFloat(p.lng) }))
                .filter(p => Number.isFinite(p.lat) && Number.isFinite(p.lng));
        } catch (_) {
            return [];
        }
    }

    async function initBoundaryEditor() {
        const hidden = document.getElementById('boundary_polygon');
        const status = document.getElementById('boundaryStatus');
        const initial = parseInitialBoundary(hidden.value);

        await loadGoogleMapsAPI();

        const map = new google.maps.Map(document.getElementById('boundaryMap'), {
            center: initial[0] || { lat: 35.6762, lng: 139.6503 },
            zoom: initial.length ? 17 : 10,
            mapTypeId: google.maps.MapTypeId.SATELLITE,
            streetViewControl: false,
            fullscreenControl: true,
            draggableCursor: 'crosshair',
        });

        const polygon = new google.maps.Polygon({
            map,
            paths: initial,
            editable: true,
            strokeColor: '#FF0000',
            strokeOpacity: 0.8,
            strokeWeight: 3,
            fillColor: '#FF0000',
            fillOpacity: 0.25,
        });
        const path = polygon.getPath();

        function sync() {
            const points = path.getArray().map(latLng => ({ lat: latLng.lat(), lng: latLng.lng() }));
            hidden.value = points.length ? JSON.stringify(points) : '';

            if (points.length === 0) {
                status.textContent = '境界が未設定です（このまま保存すると仮登録になります）。';
                status.className = 'mt-2 text-sm text-gray-600';
            } else if (points.length < MIN_POINTS) {
                status.textContent = `頂点 ${points.length} 点。あと ${MIN_POINTS - points.length} 点以上追加してください。`;
                status.className = 'mt-2 text-sm text-red-600';
            } else {
                const area = google.maps.geometry.spherical.computeArea(path);
                status.textContent = `頂点 ${points.length} 点／面積 約 ${Math.round(area).toLocaleString()} ㎡`;
                status.className = 'mt-2 text-sm text-gray-600';
            }
        }

        ['insert_at', 'remove_at', 'set_at'].forEach(eventName => path.addListener(eventName, sync));
        map.addListener('click', event => path.push(event.latLng));
        polygon.addListener('click', event => {
            if (event.vertex === undefined && event.edge === undefined) {
                path.push(event.latLng);
            }
        });

        document.getElementById('undoPointBtn').addEventListener('click', () => {
            if (path.getLength() > 0) path.pop();
        });
        document.getElementById('clearPointsBtn').addEventListener('click', () => path.clear());
        document.getElementById('currentLocationBtn').addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('この端末では現在地を取得できません。');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                position => {
                    map.setCenter({ lat: position.coords.latitude, lng: position.coords.longitude });
                    map.setZoom(18);
                },
                () => alert('現在地を取得できませんでした。')
            );
        });

        if (initial.length) {
            const bounds = new google.maps.LatLngBounds();
            initial.forEach(p => bounds.extend(p));
            map.fitBounds(bounds);
        }

        document.getElementById('farmForm').addEventListener('submit', event => {
            const count = path.getLength();
            if (count > 0 && count < MIN_POINTS) {
                event.preventDefault();
                alert(`境界は${MIN_POINTS}点以上で描いてください。境界なしで保存する場合は「境界をクリア」を押してください。`);
            }
        });

        sync();
    }
</script>
@endsection
