@extends('layouts.dashboard')

@section('title', '推定結果閲覧')
@section('header-title', '推定結果閲覧')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- 検索フォーム（圃場管理ページと同レイアウト） -->
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('estimation-results.index') }}" class="space-y-4">
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
                    <a href="{{ route('estimation-results.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        リセット
                    </a>
                </div>
            </form>
        </div>

        <!-- 結果表示（圃場一覧） -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">圃場一覧</h2>
                <p class="text-gray-600 mt-1">全{{ $farms->total() }}件中 {{ $farms->firstItem() ?? 0 }}-{{ $farms->lastItem() ?? 0 }}件を表示</p>
                <p class="text-gray-600">推定結果を確認したい圃場を選択してください。</p>
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
                                <tr class="hover:bg-gray-50">
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $farm->id }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">
                                        <a href="{{ route('estimation-results.farm-dates', ['farm' => $farm->id]) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                            {{ $farm->farm_name }}
                                        </a>
                                    </td>
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
@endsection


