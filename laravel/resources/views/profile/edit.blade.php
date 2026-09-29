@extends('layouts.dashboard')

@section('title', '設定')
@section('header-title', '設定')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <h2 class="text-lg font-medium text-gray-900">アカウント情報</h2>
                <p class="mt-1 text-sm text-gray-600">
                    名前・メールアドレス・パスワードの変更はモバイルアプリから行ってください。
                </p>

                <dl class="mt-6 divide-y divide-gray-100">
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">名前</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $user->name }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">メールアドレス</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $user->email }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">所属</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $user->ja_name ?: '未設定' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">権限</dt>
                        <dd class="text-sm text-gray-900 col-span-2">
                            {{ $user->isAdmin() ? '管理者' : '一般ユーザー' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        @unless ($user->isAdmin())
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">管理者権限を有効にする</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        管理者から伝えられた管理者キーを入力してください。一度有効にすると、次回からは通常のログインで管理者として使えます。
                    </p>

                    <form method="POST" action="{{ route('profile.admin-key') }}" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="admin_key" class="block text-sm font-medium text-gray-700">管理者キー</label>
                            <input id="admin_key" name="admin_key" type="password" autocomplete="off" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('admin_key')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            有効にする
                        </button>
                    </form>
                </div>
            </div>
        @endunless
    </div>
</div>
@endsection
