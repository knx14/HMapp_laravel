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
<div>
    <div class="text-3xl font-bold">最近の推定結果</div>
</div>
@endsection