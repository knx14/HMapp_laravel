<x-guest-layout>
@php
    $isLogin = request()->get('mode', 'login') === 'login';
@endphp
<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white shadow-lg rounded-xl w-full max-w-md p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="mb-2">
                <img src="/images/loginIcon.png" alt="Login Icon" class="w-10 h-10" />
            </div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $isLogin ? '管理者ログイン' : '新規登録' }}</h2>
        </div>
        <div>
            <form method="POST" action="{{ $isLogin ? route('login') : route('register') }}">
                @csrf
                @if(!$isLogin)
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">名前</label>
                        <input id="name" name="name" type="text" required autofocus class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" value="{{ old('name') }}">
                        @error('name')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div class="mt-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                    <input id="email" name="email" type="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" value="{{ old('email') }}">
                    @error('email')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mt-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">パスワード</label>
                    <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    @error('password')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
                </div>
                @if(!$isLogin)
                    <div class="mt-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">パスワード（確認）</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        @error('password_confirmation')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mt-4">
                        <label for="admin_key" class="block text-sm font-medium text-gray-700">管理者キー</label>
                        <input id="admin_key" name="admin_key" type="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" autocomplete="off">
                        @error('admin_key')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
                        <p class="mt-1 text-xs text-gray-500">管理者キーを入力してください。管理者のみが新規登録できます。</p>
                    </div>
                @endif
                @if($isLogin)
                <div class="flex items-center mt-4">
                    <input id="remember_me" name="remember" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">ログイン状態を保持</label>
                </div>
                @endif
                <button type="submit" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="w-5 h-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-3A2.25 2.25 0 008.25 5.25V9m7.5 0v10.5A2.25 2.25 0 0113.5 21h-3A2.25 2.25 0 018.25 19.5V9m7.5 0h-11.5" />
                    </svg>
                    {{ $isLogin ? 'ログイン' : '新規登録' }}
                </button>
            </form>
            <div class="mt-6 text-center">
                <a href="?mode={{ $isLogin ? 'register' : 'login' }}" class="text-blue-600 hover:underline font-medium">
                    {{ $isLogin ? '新規登録はこちら' : 'ログインはこちら' }}
                </a>
            </div>
        </div>
    </div>
</div>
</x-guest-layout> 