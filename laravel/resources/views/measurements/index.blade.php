@extends('layouts.dashboard')

@section('title', '測定データ閲覧')
@section('header-title', '測定データ閲覧')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif

        <!-- 検索フォーム -->
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('measurements.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">圃場名</label>
                        <input type="text" name="farm_name" value="{{ $filters->farmName }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="圃場名">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">栽培方法</label>
                        <input type="text" name="cultivation_method" value="{{ $filters->cultivationMethod }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="栽培方法（例：水田、畑）">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">作物種別</label>
                        <input type="text" name="crop_type" value="{{ $filters->cropType }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="作物種別（例：米、トマト）">
                    </div>
                    @if($isAdmin)
                        <div>
                            <label class="block font-semibold mb-1">ユーザー名</label>
                            <input type="text" name="user_name" value="{{ $filters->userName }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="ユーザー名">
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">削除済みの表示</label>
                            <select name="trashed" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                @foreach($trashedOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filters->trashed === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="flex flex-row gap-4 mt-6">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        検索
                    </button>
                    <a href="{{ route('measurements.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        リセット
                    </a>
                </div>
            </form>
        </div>

        @if($isAdmin)
            {{-- 旧アップロード管理の手動登録・結果入力 --}}
            <div class="bg-white rounded-2xl shadow p-8 mb-8">
                <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">推定結果の入力待ち</h2>
                    <a href="{{ route('upload-management.create') }}" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        測定を手動で登録
                    </a>
                </div>
                @if($pendingUploads->isEmpty())
                    <p class="text-gray-500">入力待ちの測定はありません。</p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach($pendingUploads as $pending)
                            <li class="py-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm text-gray-800">
                                    <span class="font-medium">ID: {{ $pending->id }}</span>
                                    <span class="ml-2">{{ $pending->farm?->farm_name ?? '-' }}</span>
                                    <span class="ml-2 text-gray-600">{{ $pending->measuredAtLabel() }}</span>
                                </div>
                                @if($pending->status === \App\Models\Upload::STATUS_PROCESSING && $pending->analysisResult)
                                    <a href="{{ route('estimation-results.input-result-value', ['farm' => $pending->farm_id, 'analysisResult' => $pending->analysisResult->id]) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">測定値を入力</a>
                                @else
                                    <a href="{{ route('estimation-results.input', ['farm' => $pending->farm_id]) }}?upload_id={{ $pending->id }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">測定点を入力</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form id="measurement-export-form" method="POST" action="{{ route('measurements.export') }}">
            @csrf
            @foreach($filters->toQuery() as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </form>

        <!-- 結果表示 -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">測定データ一覧</h2>
                    <p class="text-gray-600 mt-1">全{{ $uploads->total() }}件中 {{ $uploads->firstItem() ?? 0 }}-{{ $uploads->lastItem() ?? 0 }}件を表示。行をクリックすると詳細を表示します。</p>
                    @if($isAdmin)
                        <p class="text-gray-500 text-sm mt-1">管理者の CSV には生データ（実数・虚数）が付きます。1回 {{ config('measurements.admin_export_limit') }} 件までです。</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" form="measurement-export-form" name="scope" value="selected"
                            class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        選択した行をダウンロード
                    </button>
                    <button type="submit" form="measurement-export-form" name="scope" value="all"
                            class="inline-flex items-center bg-white border border-green-600 text-green-700 hover:bg-green-50 font-bold py-2 px-4 rounded-lg transition"
                            @disabled($uploads->total() === 0)>
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        条件に一致する全件をダウンロード
                    </button>
                    <button type="submit" form="measurement-export-form" formaction="{{ route('measurements.destroy-selected') }}" id="measurement-delete-button"
                            class="inline-flex items-center bg-white border border-red-600 text-red-700 hover:bg-red-50 font-bold py-2 px-4 rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        選択した行を削除
                    </button>
                </div>
            </div>

            @if($uploads->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <input type="checkbox" id="measurement-select-all" class="rounded" aria-label="このページをすべて選択">
                                </th>
                                @if($isAdmin)
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ユーザー名</th>
                                @endif
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">圃場名</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">栽培方式</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">作物種別</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">測定日</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">測定番号</th>
                                @if($isAdmin)
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">削除日時</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($uploads as $upload)
                                <tr class="measurement-row cursor-pointer {{ $upload->trashed() ? 'bg-gray-100 text-gray-400' : 'text-gray-900 hover:bg-gray-50' }}" data-upload-id="{{ $upload->id }}">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="upload_ids[]" value="{{ $upload->id }}" form="measurement-export-form" class="measurement-row-check rounded" aria-label="選択">
                                    </td>
                                    @if($isAdmin)
                                        <td class="px-6 py-4 text-sm">{{ $upload->user_name ?? '-' }}</td>
                                    @endif
                                    <td class="px-6 py-4 text-sm">{{ $upload->farm_name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $upload->cultivation_method }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $upload->crop_type }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $upload->measuredAtLabel() }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $upload->measurement_number }}</td>
                                    @if($isAdmin)
                                        <td class="px-6 py-4 text-sm">{{ $upload->deleted_at?->copy()->timezone(config('measurements.display_timezone'))->format('Y-m-d H:i:s') }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- ページネーション -->
                <div class="px-8 py-6 border-t border-gray-200">
                    {{ $uploads->links() }}
                </div>
            @else
                <div class="px-8 py-12 text-center">
                    <div class="text-gray-500 text-lg">検索条件に一致する測定データが見つかりませんでした。</div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- 詳細ポップアップ -->
<div id="measurement-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">詳細データ</h3>
            <button type="button" id="measurement-modal-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="閉じる">&times;</button>
        </div>
        <div class="p-6">
            <div id="measurement-modal-loading" class="text-gray-500">読み込み中…</div>
            <div id="measurement-modal-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
            <div id="measurement-modal-deleted" class="hidden bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded mb-4"></div>
            <div id="measurement-modal-body" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <dl id="measurement-detail-list" class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm"></dl>

                    <form id="measurement-edit-form" class="hidden mt-6 space-y-3 border-t border-gray-200 pt-4">
                        <h4 class="font-semibold text-gray-800">項目の修正（管理者）</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-sm">測定日
                                <input type="date" name="measurement_date" required class="mt-1 w-full border rounded px-2 py-1">
                            </label>
                            <label class="text-sm">時刻（空欄なら日付のみ）
                                <input type="time" name="measurement_time" class="mt-1 w-full border rounded px-2 py-1">
                            </label>
                            <label class="text-sm">測定番号
                                <input type="number" name="measurement_number" min="1" step="1" class="mt-1 w-full border rounded px-2 py-1">
                            </label>
                        </div>
                        <div id="measurement-edit-values" class="grid grid-cols-2 gap-3"></div>
                        <p class="text-xs text-gray-500">ユーザー名・圃場名・栽培方法・作物種別の修正は未対応です。</p>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">保存</button>
                    </form>
                </div>
                <div>
                    <div id="measurement-map" class="w-full h-80 rounded-lg bg-gray-100"></div>
                    <p id="measurement-map-note" class="text-xs text-gray-500 mt-2"></p>
                    <button type="button" id="measurement-location-save" class="hidden mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">この地点で保存</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const API_KEY = '{{ env('GOOGLE_MAPS_API_KEY') }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const detailUrl = (id) => `{{ url('/measurements') }}/${id}`;

    const modal = document.getElementById('measurement-modal');
    const loading = document.getElementById('measurement-modal-loading');
    const errorBox = document.getElementById('measurement-modal-error');
    const deletedBox = document.getElementById('measurement-modal-deleted');
    const body = document.getElementById('measurement-modal-body');
    const list = document.getElementById('measurement-detail-list');
    const editForm = document.getElementById('measurement-edit-form');
    const editValues = document.getElementById('measurement-edit-values');
    const mapNote = document.getElementById('measurement-map-note');
    const locationSave = document.getElementById('measurement-location-save');

    let current = null;
    let map = null;
    let marker = null;
    let polygon = null;
    let pendingPosition = null;

    document.getElementById('measurement-select-all')?.addEventListener('change', function () {
        document.querySelectorAll('.measurement-row-check').forEach((checkbox) => {
            checkbox.checked = this.checked;
        });
    });

    document.getElementById('measurement-delete-button')?.addEventListener('click', (event) => {
        const count = document.querySelectorAll('.measurement-row-check:checked').length;
        if (count === 0) {
            event.preventDefault();
            alert('削除する行を選択してください。');
            return;
        }
        if (!confirm(`選択した${count}件の測定を削除します。よろしいですか？`)) {
            event.preventDefault();
        }
    });

    document.querySelectorAll('.measurement-row').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('input, a, button')) return;
            openDetail(row.dataset.uploadId);
        });
    });

    const openId = new URLSearchParams(window.location.search).get('open');
    if (openId) {
        openDetail(openId);
    }

    document.getElementById('measurement-modal-close').addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    function closeModal() {
        modal.classList.add('hidden');
        current = null;
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    async function request(url, method = 'GET', payload = null) {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: payload ? JSON.stringify(payload) : null,
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            const messages = json.errors ? Object.values(json.errors).flat() : [json.message || '処理に失敗しました。'];
            throw new Error(messages.join('\n'));
        }
        return json.data;
    }

    async function openDetail(id) {
        modal.classList.remove('hidden');
        loading.classList.remove('hidden');
        body.classList.add('hidden');
        errorBox.classList.add('hidden');
        deletedBox.classList.add('hidden');
        try {
            render(await request(detailUrl(id)));
        } catch (error) {
            showError(error.message);
        } finally {
            loading.classList.add('hidden');
        }
    }

    function formatValue(item) {
        return item.value === null ? '-' : `${item.value} ${item.unit ?? ''}`.trim();
    }

    function render(data) {
        current = data;
        body.classList.remove('hidden');

        if (data.deleted_at) {
            deletedBox.textContent = `この測定は ${data.deleted_at} に削除されています。修正はできません。`;
            deletedBox.classList.remove('hidden');
        }

        const rows = [];
        if (data.user_name !== null) rows.push(['ユーザー名', data.user_name ?? '-']);
        rows.push(
            ['圃場名', data.farm.name],
            ['栽培方法', data.farm.cultivation_method ?? '-'],
            ['作物種別', data.farm.crop_type ?? '-'],
            ['測定番号', data.measurement_number ?? '-'],
            ['測定日時', data.measured_at || '-'],
            ...data.values.map((item) => [item.parameter, formatValue(item)]),
            ['推定日時', data.estimated_at ?? '-'],
            ['推定モデル', data.estimation_model],
        );
        list.replaceChildren(...rows.flatMap(([label, value]) => {
            const dt = document.createElement('dt');
            dt.className = 'font-semibold text-gray-600';
            dt.textContent = label;
            const dd = document.createElement('dd');
            dd.className = 'col-span-2 text-gray-900';
            dd.textContent = value;
            return [dt, dd];
        }));

        renderEditForm(data);
        renderMap(data);
    }

    function renderEditForm(data) {
        editForm.classList.toggle('hidden', !data.can.edit);
        if (!data.can.edit) return;

        editForm.measurement_date.value = data.measurement_date ?? '';
        editForm.measurement_time.value = data.measurement_time ?? '';
        editForm.measurement_number.value = data.measurement_number ?? '';
        editValues.replaceChildren(...data.values.map((item) => {
            const label = document.createElement('label');
            label.className = 'text-sm';
            label.textContent = `${item.parameter}（${item.unit ?? ''}）`;
            const input = document.createElement('input');
            input.type = 'number';
            input.step = 'any';
            input.name = `values[${item.parameter}]`;
            input.dataset.parameter = item.parameter;
            input.value = item.value ?? '';
            input.className = 'mt-1 w-full border rounded px-2 py-1';
            label.appendChild(input);
            return label;
        }));
    }

    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!current) return;
        errorBox.classList.add('hidden');

        const values = {};
        editValues.querySelectorAll('input').forEach((input) => {
            values[input.dataset.parameter] = input.value === '' ? null : Number(input.value);
        });

        try {
            const data = await request(detailUrl(current.id), 'PUT', {
                measurement_date: editForm.measurement_date.value,
                measurement_time: editForm.measurement_time.value || null,
                measurement_number: editForm.measurement_number.value === '' ? null : Number(editForm.measurement_number.value),
                values,
            });
            render(data);
            alert('保存しました。一覧の表示は再読み込みで更新されます。');
        } catch (error) {
            showError(error.message);
        }
    });

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

    function normalizeBoundary(raw) {
        let data = raw;
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (_) { return []; }
        }
        if (!Array.isArray(data)) return [];
        return data.map((p) => {
            if (Array.isArray(p) && p.length >= 2) return { lat: parseFloat(p[0]), lng: parseFloat(p[1]) };
            if (p && 'lat' in p && 'lng' in p) return { lat: parseFloat(p.lat), lng: parseFloat(p.lng) };
            if (p && 'latitude' in p && 'longitude' in p) return { lat: parseFloat(p.latitude), lng: parseFloat(p.longitude) };
            return null;
        }).filter(Boolean);
    }

    async function renderMap(data) {
        locationSave.classList.add('hidden');
        pendingPosition = null;
        mapNote.textContent = '';

        try {
            await loadGoogleMaps();
        } catch (_) {
            mapNote.textContent = '地図を読み込めませんでした。';
            return;
        }

        if (!map) {
            map = new google.maps.Map(document.getElementById('measurement-map'), {
                center: { lat: 35.6762, lng: 139.6503 },
                zoom: 16,
                mapTypeId: google.maps.MapTypeId.SATELLITE,
                streetViewControl: false,
            });
        }
        marker?.setMap(null);
        polygon?.setMap(null);
        marker = null;
        polygon = null;

        const bounds = new google.maps.LatLngBounds();
        const boundary = normalizeBoundary(data.farm.boundary_polygon);
        if (boundary.length > 0) {
            polygon = new google.maps.Polygon({
                paths: boundary,
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.15,
                map,
            });
            boundary.forEach((point) => bounds.extend(point));
        }

        if (data.location) {
            const position = { lat: data.location.latitude, lng: data.location.longitude };
            marker = new google.maps.Marker({ position, map, draggable: data.can.move_location });
            bounds.extend(position);
            if (data.can.move_location) {
                mapNote.textContent = 'ピンをドラッグすると測定地点を調整できます。';
                marker.addListener('dragend', () => onMarkerMoved(position));
            }
        } else {
            mapNote.textContent = '測定地点が登録されていません。';
        }

        if (!bounds.isEmpty()) {
            map.fitBounds(bounds);
            if (boundary.length === 0) map.setZoom(18);
        }
    }

    function onMarkerMoved(original) {
        const moved = marker.getPosition();
        if (polygon && !google.maps.geometry.poly.containsLocation(moved, polygon)
            && !confirm('圃場の境界の外です。この位置に動かしますか？')) {
            marker.setPosition(original);
            return;
        }
        pendingPosition = { latitude: moved.lat(), longitude: moved.lng() };
        locationSave.classList.remove('hidden');
    }

    locationSave.addEventListener('click', async () => {
        if (!current || !pendingPosition) return;
        errorBox.classList.add('hidden');
        try {
            const data = await request(`${detailUrl(current.id)}/location`, 'PATCH', pendingPosition);
            render(data);
            mapNote.textContent = '測定地点を保存しました。';
        } catch (error) {
            showError(error.message);
        }
    });
})();
</script>
@endsection
