@extends('layouts.dashboard')

@section('title', '管理者ダッシュボード')
@section('header-title', '管理者ダッシュボード')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">

    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500 flex items-center gap-x-6">
        <div class="p-3 flex-shrink-0">
            <img src="{{ asset('images/usersIcon.png') }}" alt="ユーザーアイコン" class="h-8 w-8 text-blue-500">
        </div>
        <div>
            <div class="text-4xl font-bold text-gray-800 leading-none">
                {{ number_format($userCount) }}
            </div>
            <p class="text-gray-500 text-lg mt-2">総ユーザー数</p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500 flex items-center gap-x-6">
        <div class="p-3 flex-shrink-0">
            <img src="{{ asset('images/uploadIcon.png') }}" alt="アップロードアイコン" class="h-8 w-8 text-green-500">
        </div>
        <div>
            <div class="text-4xl font-bold text-gray-800 leading-none">
                {{ number_format($uploadCount) }}
            </div>
            <p class="text-gray-500 text-lg mt-2">アップロード数</p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-yellow-500 flex items-center gap-x-6">
        <div class="p-3 flex-shrink-0">
            <img src="{{ asset('images/outupIcon.png') }}" alt="演算処理アイコン" class="h-8 w-8 text-yellow-500">
        </div>
        <div>
            <div class="text-4xl font-bold text-gray-800 leading-none">
                {{ number_format($completedCount) }}
            </div>
            <p class="text-gray-500 text-lg mt-2">演算処理数</p>
        </div>
    </div>

</div>
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">最近の推定結果</h2>
            <p class="text-gray-500 mt-1">全ユーザーの推定完了を新しい順に10件表示します。行を開くと測定データの詳細を表示します。</p>
        </div>
        <a href="{{ route('measurements.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold">測定データ閲覧へ</a>
    </div>

    @if($recentResults->isEmpty())
        <p class="px-6 py-10 text-gray-500">推定結果はまだありません。</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">ユーザー名</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">圃場名</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">栽培方式</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">作物種別</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">測定日</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">測定番号</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($recentResults as $result)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $result->user_name ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('measurements.index', ['open' => $result->id]) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                    {{ $result->farm_name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $result->cultivation_method ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $result->crop_type ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $result->measuredAtLabel() ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $result->measurement_number ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection