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
            <p class="text-gray-700"><span class="font-semibold">権限:</span> {{ $user->isAdmin() ? '管理者' : '一般ユーザー' }}</p>
            @if($user->isAdmin())
                <p class="text-gray-700"><span class="font-semibold">管理者になった日時:</span> {{ $user->admin_granted_at?->format('Y-m-d H:i') ?? '-' }}</p>

                @if(! $user->is(auth()->user()))
                    <form method="POST" action="{{ route('user-management.revoke-admin', $user) }}" class="mt-6"
                          onsubmit="return confirm(@js(($user->name ?? '') . ' さんを一般ユーザーに戻します。よろしいですか？'));">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            一般ユーザーに戻す
                        </button>
                    </form>
                @endif
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow p-8">
            <h2 class="text-xl font-semibold mb-4">管理者権限の履歴</h2>
            @if($roleEvents->isEmpty())
                <p class="text-gray-500">履歴はありません。</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">日時</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">内容</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">操作した人</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-700">IPアドレス</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($roleEvents as $event)
                            <tr>
                                <td class="px-4 py-2 text-gray-900">{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2 {{ $event->action === 'grant_failed' ? 'text-red-700' : 'text-gray-900' }}">{{ $event->actionLabel() }}</td>
                                <td class="px-4 py-2 text-gray-900">{{ $event->actor?->name ?? 'サーバー上のコマンド' }}</td>
                                <td class="px-4 py-2 text-gray-900">{{ $event->ip_address ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
