@php
    $tab = request('tab', 'login');
@endphp
<x-guest-layout>
    <div class="w-full max-w-md mx-auto mt-10 bg-white p-8 rounded-xl shadow-md">
        <div class="flex flex-col items-center mb-6">
            <div class="mb-2">
                <img src="/images/loginIcon.png" alt="ログインアイコン" class="h-16 w-16 mx-auto" />
            </div>
            <h2 class="text-3xl font-bold mb-4 text-center">管理者ログイン</h2>
        </div>
        <div class="flex mb-6 border-b">
            <a href="?tab=login" class="flex-1 text-center py-2 font-bold transition-all border-b-2 {{ $tab == 'login' ? 'border-blue-600 text-blue-600 bg-gray-50' : 'border-transparent text-gray-400 bg-white' }}">ログイン</a>
            <a href="?tab=register" class="flex-1 text-center py-2 font-bold transition-all border-b-2 {{ $tab == 'register' ? 'border-blue-600 text-blue-600 bg-gray-50' : 'border-transparent text-gray-400 bg-white' }}">新規登録</a>
        </div>
        @if($tab == 'login')
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-bold mb-2">メールアドレス</label>
                    <input id="email" class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@example.com" />
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-bold mb-2">パスワード</label>
                    <input id="password" class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" type="password" name="password" required autocomplete="current-password" placeholder="********" />
                </div>
                <div class="flex items-center mb-4">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                    <label for="remember_me" class="ml-2 text-sm text-gray-600">ログイン状態を保持</label>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <a class="text-sm text-gray-500 hover:underline" href="{{ route('password.request') }}">パスワードをお忘れですか？</a>
                    <button type="submit" class="flex items-center justify-center px-8 py-2 bg-blue-600 text-white font-bold rounded-md shadow hover:bg-blue-700 transition w-full">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M3 10a1 1 0 011-1h8.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L12.586 11H4a1 1 0 01-1-1z" /></svg>
                        ログイン
                    </button>
                </div>
            </form>
        @else
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-bold mb-2">名前</label>
                    <input id="name" class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="お名前" />
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-bold mb-2">メールアドレス</label>
                    <input id="email" class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="sample@example.com" />
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-bold mb-2">パスワード</label>
                    <input id="password" class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" type="password" name="password" required autocomplete="new-password" placeholder="********" />
                </div>
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-gray-700 font-bold mb-2">パスワード（確認）</label>
                    <input id="password_confirmation" class="block w-full rounded-md border border-gray-300 px-4 py-3 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="********" />
                </div>
                <div class="flex items-center justify-between">
                    <a class="text-sm text-gray-500 hover:underline" href="?tab=login">すでに登録済みの方はこちら</a>
                    <button type="submit" class="flex items-center justify-center px-8 py-2 bg-blue-600 text-white font-bold rounded-md shadow hover:bg-blue-700 transition w-full">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M3 10a1 1 0 011-1h8.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L12.586 11H4a1 1 0 01-1-1z" /></svg>
                        新規登録
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-guest-layout> 