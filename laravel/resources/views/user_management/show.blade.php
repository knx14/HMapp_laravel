@extends('layouts.dashboard')

@section('title', 'ユーザー詳細')
@section('header-title', 'ユーザー詳細')

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4">
        <div class="mb-4">
            <a href="{{ route('user-management.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">ユーザー一覧に戻る</a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow p-8 mb-6">
            <h2 class="text-xl font-semibold mb-4">ユーザー情報</h2>
            <p class="text-gray-700"><span class="font-semibold">ID:</span> {{ $user->id }}</p>
            <p class="text-gray-700"><span class="font-semibold">名前:</span> {{ $user->name ?? '-' }}</p>
            <p class="text-gray-700"><span class="font-semibold">メール:</span> {{ $user->email ?? '-' }}</p>
            <p class="text-gray-700"><span class="font-semibold">所属:</span> {{ $user->ja_name ?? '-' }}</p>
        </div>
    </div>
</div>
@endsection
