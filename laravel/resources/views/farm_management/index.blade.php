@extends('layouts.dashboard')

@section('title', '圃場管理')
@section('header-title', '圃場管理')

@section('content')
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 mx-4">
        {{ session('success') }}
    </div>
@endif

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('farm-management.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">圃場名</label>
                        <input type="text" name="farm_name" value="{{ $input['farm_name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="圃場名">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">栽培方法</label>
                        <input type="text" name="cultivation_method" value="{{ $input['cultivation_method'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="栽培方法（例：水田、畑）">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">作物種別</label>
                        <input type="text" name="crop_type" value="{{ $input['crop_type'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="作物種別（例：米、トマト）">
                    </div>
                    @if($isAdmin)
                        <div>
                            <label class="block font-semibold mb-1">ユーザー名</label>
                            <input type="text" name="user_name" value="{{ $input['user_name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="ユーザー名">
                        </div>
                    @endif
                    <div>
                        <label class="block font-semibold mb-1">並べ替え</label>
                        <select name="sort" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @foreach($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
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

        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">圃場一覧</h2>
                <p class="text-gray-600 mt-1">全{{ $farms->total() }}件中 {{ $farms->firstItem() ?? 0 }}-{{ $farms->lastItem() ?? 0 }}件を表示</p>
            </div>
            <a href="{{ route('farm-management.create') }}"
               class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                新規圃場を登録
            </a>
        </div>

        @if($farms->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($farms as $farm)
                    <div class="farm-card bg-white rounded-2xl shadow hover:shadow-lg transition cursor-pointer p-6 flex flex-col {{ $farm->hidden_at ? 'opacity-60' : '' }}"
                         data-detail-url="{{ route('farm-management.show', $farm) }}">
                        <div class="flex justify-between items-start gap-2">
                            <h3 class="text-lg font-semibold text-gray-800 break-all">
                                <a href="{{ route('farm-management.show', $farm) }}" class="hover:text-blue-700">{{ $farm->farm_name }}</a>
                            </h3>
                            <div class="flex flex-wrap gap-1 justify-end">
                                @if($farm->hidden_at)
                                    <span class="inline-flex rounded-full bg-gray-200 px-2 py-1 text-xs font-semibold text-gray-700">非表示中</span>
                                @endif
                                @if($farm->isProvisional())
                                    <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">仮登録</span>
                                @endif
                            </div>
                        </div>
                        <dl class="mt-3 grid grid-cols-3 gap-y-1 text-sm">
                            @if($isAdmin || (int) $farm->app_user_id !== (int) auth()->id())
                                <dt class="text-gray-500">ユーザー名</dt>
                                <dd class="col-span-2 text-gray-900">{{ $farm->appUser?->name ?? '-' }}</dd>
                            @endif
                            <dt class="text-gray-500">栽培方法</dt>
                            <dd class="col-span-2 text-gray-900">{{ $farm->cultivation_method ?: '-' }}</dd>
                            <dt class="text-gray-500">作物種別</dt>
                            <dd class="col-span-2 text-gray-900">{{ $farm->crop_type ?: '-' }}</dd>
                            <dt class="text-gray-500">登録日</dt>
                            <dd class="col-span-2 text-gray-900">{{ $farm->created_at?->copy()->timezone('Asia/Tokyo')->format('Y-m-d') }}</dd>
                        </dl>
                        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                            @can('manage', $farm)
                                <a href="{{ route('farm-management.edit', $farm) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">編集</a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $farms->links() }}
            </div>
        @else
            <div class="bg-white rounded-2xl shadow px-8 py-12 text-center">
                <div class="text-gray-500 text-lg">検索条件に一致する圃場が見つかりませんでした。</div>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.farm-card').forEach(card => {
            card.addEventListener('click', function(event) {
                if (event.target.closest('a, button')) return;
                window.location.href = this.dataset.detailUrl;
            });
        });
    });
</script>
@endsection
