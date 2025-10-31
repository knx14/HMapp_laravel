@extends('layouts.dashboard')

@section('title', 'ユーザー管理')
@section('header-title', 'ユーザー管理')

@section('content')
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 mx-4">
        {{ session('success') }}
    </div>
@endif

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- 検索フォーム -->
        <div class="bg-white rounded-2xl shadow p-8 mb-8">
            <form method="GET" action="{{ route('user-management.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block font-semibold mb-1">Cognito Sub</label>
                        <input type="text" name="cognito_sub" value="{{ $filters['cognito_sub'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Cognito Sub を入力">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">名前</label>
                        <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="名前 を入力">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">登録JA名</label>
                        <input type="text" name="ja_name" value="{{ $filters['ja_name'] ?? '' }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="登録JA名 を入力">
                    </div>
                </div>
                <div class="flex flex-row gap-4 mt-6">
                    <button type="submit" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        検索
                    </button>
                    <a href="{{ route('user-management.index') }}" class="flex items-center bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-2 px-8 rounded transition text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        リセット
                    </a>
                </div>
            </form>
        </div>

        <!-- 結果表示 -->
        <div class="bg-white rounded-2xl shadow overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">ユーザー一覧</h2>
                        <p class="text-gray-600 mt-1">全{{ $users->total() }}件中 {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }}件を表示</p>
                    </div>
                    <a href="{{ route('user-management.create') }}" 
                       class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        新規ユーザー登録
                    </a>
                </div>
            </div>

            @if($users->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">ID</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">Cognito Sub</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">名前</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">メールアドレス</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">JA名</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">作成日</th>
                                <th class="px-8 py-4 text-left text-sm font-semibold text-gray-700">更新日</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $user->id }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $user->cognito_sub ?? '-' }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $user->name ?? '-' }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $user->email ?? '-' }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $user->ja_name ?? '-' }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td class="px-8 py-4 text-sm text-gray-900">{{ $user->updated_at ? $user->updated_at->format('Y-m-d H:i') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- ページネーション -->
                <div class="px-8 py-6 border-t border-gray-200">
                    {{ $users->appends($filters)->links() }}
                </div>
            @else
                <div class="px-8 py-12 text-center">
                    <div class="text-gray-500 text-lg">検索条件に一致するユーザーが見つかりませんでした。</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
