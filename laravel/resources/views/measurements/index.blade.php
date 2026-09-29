@extends('layouts.dashboard')

@section('title', '測定データ閲覧')
@section('header-title', '測定データ閲覧')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
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
                    <p class="text-gray-600 mt-1">全{{ $uploads->total() }}件中 {{ $uploads->firstItem() ?? 0 }}-{{ $uploads->lastItem() ?? 0 }}件を表示</p>
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
                                <tr class="{{ $upload->trashed() ? 'bg-gray-100 text-gray-400' : 'text-gray-900 hover:bg-gray-50' }}">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="upload_ids[]" value="{{ $upload->id }}" form="measurement-export-form" class="measurement-row-check rounded" aria-label="選択">
                                    </td>
                                    @if($isAdmin)
                                        <td class="px-6 py-4 text-sm">{{ $upload->user_name ?? '-' }}</td>
                                    @endif
                                    <td class="px-6 py-4 text-sm">{{ $upload->farm_name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $upload->cultivation_method }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $upload->crop_type }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $upload->measurement_date?->format('Y-m-d') }}</td>
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

<script>
document.getElementById('measurement-select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.measurement-row-check').forEach((checkbox) => {
        checkbox.checked = this.checked;
    });
});
</script>
@endsection
