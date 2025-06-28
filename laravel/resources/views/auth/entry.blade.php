@php
    $tab = request('tab', 'login');
@endphp
<x-guest-layout>
    <div class="w-full max-w-md mx-auto mt-10 bg-white p-8 rounded-xl shadow-md">
        <div class="flex flex-col items-center mb-6">
            <div class="mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.104-.896 2-2 2s-2-.896-2-2 .896-2 2-2 2 .896 2 2zm0 0c0 1.104.896 2 2 2s2-.896 2-2-.896-2-2-2-2 .896-2 2zm0 0v2m0 4h.01" /></svg>
            </div>
            <h2 class="text-2xl font-bold mb-2">管理者ログイン</h2>
        </div>
        <div class="flex mb-6">
            <a href="?tab=login" class="flex-1 text-center py-2 rounded-t-lg border-b-2 transition-all {{ $tab == 'login' ? 'border-blue-600 text-blue-600 font-bold bg-gray-100' : 'border-gray-200 text-gray-500 bg-white' }}">ログイン</a>
            <a href="?tab=register" class="flex-1 text-center py-2 rounded-t-lg border-b-2 transition-all {{ $tab == 'register' ? 'border-blue-600 text-blue-600 font-bold bg-gray-100' : 'border-gray-200 text-gray-500 bg-white' }}">新規登録</a>
        </div>
        @if($tab == 'login')
            <!-- ログインフォーム -->
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div>
                    <x-input-label for="email" :value="'メールアドレス'" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div class="mt-4">
                    <x-input-label for="password" :value="'パスワード'" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                        <span class="ms-2 text-sm text-gray-600">ログイン状態を保持</span>
                    </label>
                </div>
                <div class="flex items-center justify-end mt-4">
                    @if (Route::has('password.request'))
                        <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" href="{{ route('password.request') }}">
                            パスワードをお忘れですか？
                        </a>
                    @endif
                    <x-primary-button class="ms-3">
                        <i class="fas fa-sign-in-alt mr-1"></i> ログイン
                    </x-primary-button>
                </div>
            </form>
        @else
            <!-- 新規登録フォーム -->
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div>
                    <x-input-label for="name" :value="'名前'" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div class="mt-4">
                    <x-input-label for="email" :value="'メールアドレス'" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div class="mt-4">
                    <x-input-label for="password" :value="'パスワード'" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div class="mt-4">
                    <x-input-label for="password_confirmation" :value="'パスワード（確認）'" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
                <div class="flex items-center justify-end mt-4">
                    <x-primary-button class="ms-4">
                        <i class="fas fa-user-plus mr-1"></i> 新規登録
                    </x-primary-button>
                </div>
            </form>
        @endif
    </div>
</x-guest-layout> 