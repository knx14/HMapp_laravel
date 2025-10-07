@extends('layouts.dashboard')

@section('title', 'アップロード管理')
@section('header-title', 'アップロード管理')

@section('content')
<div class="py-8">
    <div class="max-w-6xl mx-auto px-4">
        <!-- 検索フォーム -->
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('upload-management.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">圃場所有者名</label>
                        <input type="text" 
                               name="owner_name" 
                               value="{{ request('owner_name') }}"
                               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                               placeholder="所有者名を入力">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">分析日付</label>
                        <input type="date" 
                               name="measurement_date" 
                               value="{{ request('measurement_date') }}"
                               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                <div class="flex flex-row gap-4 mt-6">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        検索
                    </button>
                    <a href="{{ route('upload-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        リセット
                    </a>
                </div>
            </form>
        </div>

        <!-- 一覧テーブル -->
        <div class="bg-white rounded-2xl shadow p-8">
            <h2 class="text-xl font-bold mb-4">アップロード一覧（{{ $uploads->count() }}件）</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="px-4 py-2 border-b">ID</th>
                            <th class="px-4 py-2 border-b">Farm ID</th>
                            <th class="px-4 py-2 border-b">ファイルパス</th>
                            <th class="px-4 py-2 border-b">分析日付</th>
                            <th class="px-4 py-2 border-b">圃場所有者</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($uploads as $upload)
                            <tr class="border-b hover:bg-blue-50">
                                <td class="px-4 py-2">{{ $upload->id }}</td>
                                <td class="px-4 py-2">{{ $upload->farm_id }}</td>
                                <td class="px-4 py-2">{{ $upload->file_path }}</td>
                                <td class="px-4 py-2">{{ $upload->measurement_date ? $upload->measurement_date->format('Y-m-d') : '-' }}</td>
                                <td class="px-4 py-2">{{ $upload->farm->appUser->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400">データがありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- ページネーション -->
            @if($uploads->count() > 0)
                <div class="mt-6">
                    {{ $uploads->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
